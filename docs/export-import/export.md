# Sistema de Exportação

O Laravel Raptor oferece um sistema de exportação para Excel com suporte a processamento em fila e notificações em tempo real.

## Exportação Básica

### Controller

```php
use Callcocam\LaravelRaptor\Jobs\ProcessExport;
use Callcocam\LaravelRaptor\Exports\DefaultExport;
use Maatwebsite\Excel\Facades\Excel;

public function export(Request $request)
{
    $modelClass = Product::class;
    $columns = ['id', 'name', 'price', 'status'];
    $filters = $request->only(['status', 'category_id']);
    $fileName = 'products-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
    $filePath = 'exports/' . $fileName;

    // Exportação em fila (recomendado para grandes volumes)
    ProcessExport::dispatch(
        modelClass: $modelClass,
        filters: $filters,
        columns: $columns,
        fileName: $fileName,
        filePath: $filePath,
        resourceName: 'produtos',
        userId: auth()->id(),
    );

    return back()->with('success', 'Exportação iniciada! Você será notificado quando estiver pronta.');
}
```

### Exportação Síncrona (Pequenos Volumes)

```php
public function exportSync(Request $request)
{
    $query = Product::query()
        ->when($request->status, fn($q, $status) => $q->where('status', $status));

    $columns = ['id', 'name', 'price', 'status'];
    $fileName = 'products-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

    return Excel::download(
        new DefaultExport($query, $columns),
        $fileName
    );
}
```

## ProcessExport com Contexto de Tenant

O job `ProcessExport` usa o trait `TenantAwareJob` para manter o contexto do tenant:

```php
use Callcocam\LaravelRaptor\Jobs\ProcessExport;

// O contexto do tenant é capturado automaticamente no dispatch
ProcessExport::dispatch(
    modelClass: Product::class,
    filters: ['status' => 'active'],
    columns: ['id', 'name', 'price'],
    fileName: 'produtos.xlsx',
    filePath: 'exports/produtos.xlsx',
    resourceName: 'produtos',
    userId: auth()->id(),
);
```

### Logs de Contexto

O job gera logs úteis para debug:

```
🚀 ProcessExport iniciado
- tenantId: 01kgjmcjhz37gfaejrrkbb5ks7
- TenantContext::id(): 01kgjmcjhz37gfaejrrkbb5ks7
- config(app.current_tenant_id): 01kgjmcjhz37gfaejrrkbb5ks7
- modelClass: App\Models\Product
- fileName: product-2026-02-05.xlsx

✅ ProcessExport concluído com sucesso
- totalRows: 100
- downloadUrl: /download-export/product-2026-02-05.xlsx
```

## Eventos e Notificações

Após a conclusão, o job dispara:

1. **Notificação de banco** (`ExportCompletedNotification`)
2. **Evento de broadcast** (`ExportCompleted`)

```php
// O usuário recebe notificação no banco
$user->notify(new ExportCompletedNotification(
    fileName: $fileName,
    downloadUrl: $downloadUrl,
    resourceName: 'produtos',
    wasQueued: true
));

// E evento em tempo real via WebSocket
event(new ExportCompleted(
    userId: $userId,
    modelName: 'Product',
    totalRows: 100,
    filePath: $filePath,
    fileName: $fileName
));
```

## Download

### Rota de Download

```php
// routes/web.php
Route::get('/download-export/{filename}', function (string $filename) {
    $path = 'exports/' . $filename;
    
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    
    return Storage::disk('public')->download($path);
})->name('download.export')->middleware('auth');
```

## Exportação Customizada

Veja [Jobs Customizados](./custom-jobs.md) para criar exportações com lógica específica.
