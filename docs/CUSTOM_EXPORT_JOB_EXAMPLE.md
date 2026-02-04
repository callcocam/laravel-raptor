# Custom Export Job Example

## Problema
O `ProcessExport` padrão reconstrói a query de forma genérica, o que pode não funcionar bem para filtros complexos, relacionamentos, escopos, etc.

## Solução
Criar uma classe de Job customizada com a lógica específica de construção da query.

## Exemplo de Job Customizado

```php
<?php

namespace App\Jobs;

use Callcocam\LaravelRaptor\Exports\DefaultExport;
use Callcocam\LaravelRaptor\Events\ExportCompleted;
use Callcocam\LaravelRaptor\Notifications\ExportCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $modelClass,
        protected array $filters,
        protected array $columns,
        protected string $fileName,
        protected string $filePath,
        protected string $resourceName,
        protected int|string $userId,
        protected ?string $connectionName = null,
        protected ?array $connectionConfig = null
    ) {}

    public function handle(): void
    {
        // Registra a conexão se necessário
        if ($this->connectionName && $this->connectionConfig) {
            config(["database.connections.{$this->connectionName}" => $this->connectionConfig]);
            \DB::purge($this->connectionName);
        }

        // Constrói a query com lógica ESPECÍFICA para produtos
        $query = $this->buildQuery();

        // Cria o export
        $export = new DefaultExport($query, $this->columns);

        // Gera o arquivo
        Excel::store($export, $this->filePath, 'local');

        // Notifica o usuário
        $this->notifyUser($query->count());
    }

    /**
     * Lógica CUSTOMIZADA de construção da query
     */
    protected function buildQuery()
    {
        $model = app($this->modelClass);
        if ($this->connectionName) {
            $model->setConnection($this->connectionName);
        }

        $query = $model->newQuery();

        // Carrega relacionamentos sempre
        $query->with(['category', 'brand', 'images']);

        // Aplica filtros específicos
        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['search'])) {
            $query->where(function ($q) {
                $search = $this->filters['search'];
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (isset($this->filters['price_min'])) {
            $query->where('price', '>=', $this->filters['price_min']);
        }

        if (isset($this->filters['price_max'])) {
            $query->where('price', '<=', $this->filters['price_max']);
        }

        if (isset($this->filters['created_from'])) {
            $query->where('created_at', '>=', $this->filters['created_from']);
        }

        if (isset($this->filters['created_to'])) {
            $query->where('created_at', '<=', $this->filters['created_to']);
        }

        // Aplica escopos
        if (isset($this->filters['only_active']) && $this->filters['only_active']) {
            $query->active();
        }

        // Ordenação padrão
        $query->orderBy('created_at', 'desc');

        return $query;
    }

    protected function notifyUser(int $totalRows): void
    {
        $user = \App\Models\User::find($this->userId);
        if ($user) {
            $downloadUrl = route('download.export', ['filename' => $this->fileName]);
            $user->notify(new ExportCompletedNotification(
                $this->fileName,
                $downloadUrl,
                $this->resourceName,
                true
            ));
        }

        event(new ExportCompleted(
            userId: $this->userId,
            modelName: class_basename($this->modelClass),
            totalRows: $totalRows,
            filePath: $this->filePath,
            fileName: $this->fileName
        ));
    }
}
```

## Como Usar

```php
use App\Jobs\ExportProductsJob;

ExportAction::make('export-products')
    ->model(Product::class)
    ->job(ExportProductsJob::class)  // ← Usa seu job customizado
    ->onlyFilters([
        'status',
        'search',
        'category_id',
        'price_min',
        'price_max',
        'created_from',
        'created_to',
        'only_active'
    ])
    ->exportColumns([
        'id' => 'ID',
        'name' => 'Nome',
        'sku' => 'SKU',
        'status' => 'Status',
        'price' => 'Preço',
        'category.name' => 'Categoria',
        'brand.name' => 'Marca',
    ])
    ->useJob();
```

## Vantagens

1. ✅ Controle total sobre a construção da query
2. ✅ Pode aplicar filtros complexos, relacionamentos, escopos
3. ✅ Lógica específica para cada tipo de exportação
4. ✅ Fácil de testar e manter
5. ✅ Reutilizável em outros lugares

## Job Padrão vs Job Customizado

### ProcessExport (Padrão)
- Bom para filtros simples
- Aplica filtros genéricos (whereIn, like)
- Funciona para a maioria dos casos

### Job Customizado
- Necessário para filtros complexos
- Relacionamentos específicos
- Lógica de negócio complexa
- Validações especiais
- Performance otimizada
