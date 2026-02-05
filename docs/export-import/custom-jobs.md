# Jobs de Exportação Customizados

O `ProcessExport` padrão reconstrói a query de forma genérica. Para filtros complexos, relacionamentos ou lógica específica, crie um job customizado.

## Quando Usar

- Filtros complexos que não podem ser serializados
- Eager loading específico de relacionamentos
- Transformações nos dados antes de exportar
- Lógica de negócio específica do recurso

## Exemplo: ExportProductsJob

```php
<?php

namespace App\Jobs;

use Callcocam\LaravelRaptor\Exports\DefaultExport;
use Callcocam\LaravelRaptor\Events\ExportCompleted;
use Callcocam\LaravelRaptor\Notifications\ExportCompletedNotification;
use Callcocam\LaravelRaptor\Traits\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use TenantAwareJob;

    public function __construct(
        protected array $filters,
        protected array $columns,
        protected string $fileName,
        protected string $filePath,
        protected int|string $userId
    ) {
        // Captura contexto do tenant
        $this->captureTenantContext();
    }

    public function handle(): void
    {
        // Restaura contexto do tenant
        $this->restoreTenantContext();

        // Constrói a query com lógica ESPECÍFICA
        $query = $this->buildQuery();

        // Cria o export
        $export = new DefaultExport($query, $this->columns);

        // Gera o arquivo
        Excel::store($export, $this->filePath, 'public');

        // Notifica o usuário
        $this->notifyUser($query->count());
    }

    /**
     * Lógica CUSTOMIZADA de construção da query
     */
    protected function buildQuery()
    {
        $query = \App\Models\Product::query();

        // Eager loading específico
        $query->with(['category', 'brand', 'images']);

        // Filtro de busca avançada
        if (isset($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtros simples
        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        // Filtros de range
        if (isset($this->filters['price_min'])) {
            $query->where('price', '>=', $this->filters['price_min']);
        }

        if (isset($this->filters['price_max'])) {
            $query->where('price', '<=', $this->filters['price_max']);
        }

        // Filtro de datas
        if (isset($this->filters['created_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['created_from']);
        }

        if (isset($this->filters['created_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['created_to']);
        }

        return $query;
    }

    protected function notifyUser(int $totalRows): void
    {
        $user = \App\Models\User::find($this->userId);
        
        if (!$user) {
            return;
        }

        $downloadUrl = route('download.export', ['filename' => $this->fileName]);

        // Notificação de banco
        $user->notify(new ExportCompletedNotification(
            fileName: $this->fileName,
            downloadUrl: $downloadUrl,
            resourceName: 'produtos',
            wasQueued: true
        ));

        // Evento de broadcast
        event(new ExportCompleted(
            userId: $this->userId,
            modelName: 'Product',
            totalRows: $totalRows,
            filePath: $this->filePath,
            fileName: $this->fileName
        ));
    }
}
```

## Uso

```php
use App\Jobs\ExportProductsJob;

ExportProductsJob::dispatch(
    filters: [
        'search' => 'smartphone',
        'status' => 'active',
        'price_min' => 100,
        'price_max' => 1000,
    ],
    columns: ['id', 'name', 'sku', 'price', 'category.name', 'status'],
    fileName: 'produtos-filtrados.xlsx',
    filePath: 'exports/produtos-filtrados.xlsx',
    userId: auth()->id()
);
```

## Export Customizado com Formatação

Para controle total sobre a formatação, crie uma classe Export:

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected $query
    ) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nome',
            'Categoria',
            'Preço',
            'Status',
            'Criado em',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->category?->name ?? 'Sem categoria',
            'R$ ' . number_format($product->price, 2, ',', '.'),
            $product->status === 'active' ? 'Ativo' : 'Inativo',
            $product->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
```

## Export com Conexão de Banco Diferente

Para tenants com banco separado:

```php
class ExportProductsJob implements ShouldQueue
{
    use TenantAwareJob;

    public function __construct(
        protected array $filters,
        protected ?string $connectionName = null,
        protected ?array $connectionConfig = null
    ) {
        $this->captureTenantContext();
    }

    public function handle(): void
    {
        $this->restoreTenantContext();

        // Configura conexão dinâmica se necessário
        if ($this->connectionName && $this->connectionConfig) {
            config(["database.connections.{$this->connectionName}" => $this->connectionConfig]);
            \DB::purge($this->connectionName);
        }

        $model = new \App\Models\Product();
        
        if ($this->connectionName) {
            $model->setConnection($this->connectionName);
        }

        $query = $model->newQuery();
        
        // ... resto da lógica
    }
}
```
