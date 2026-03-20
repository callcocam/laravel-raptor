<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate 
                            {--force : Força a execução sem confirmação}
                            {--seed : Executa seeders após as migrations}
                            {--fresh : Dropa todas as tabelas antes de migrar}
                            {--skip-main : Não executa migrations no banco principal}
                            {--type= : Filtra os bancos por tipo (tenant,client,store). Aceita múltiplos separados por vírgula}';

    protected $description = 'Executa migrations no banco principal e nos bancos resolvidos pelos models tenant/client/store configurados';

    protected string $defaultConnection;

    public function handle(): int
    {
        $this->defaultConnection = config('database.default');

        $this->info('🚀 Iniciando execução de migrations...');
        $this->newLine();

        $force = $this->option('force');
        $seed = $this->option('seed');
        $fresh = $this->option('fresh');
        $skipMain = (bool) $this->option('skip-main');
        $selectedTypes = $this->parseSelectedTypes();

        if (! $force && ! $this->confirm('Deseja continuar? Isso executará migrations em múltiplos bancos.', false)) {
            $this->warn('Operação cancelada.');

            return self::FAILURE;
        }

        if ($skipMain) {
            $this->info('📦 PASSO 1: Banco principal ignorado por --skip-main.');
        } else {
            // PASSO 1: Migrar banco principal (conexão default)
            $this->info('📦 PASSO 1: Migrando banco principal...');
            if (! $this->migratePrincipalDatabase($fresh, $seed)) {
                return self::FAILURE;
            }
        }

        // PASSO 2: Resolver bancos a partir dos models configurados e rodar as migrations dos paths correspondentes
        $this->newLine();
        $this->info('📦 PASSO 2: Migrando bancos (tenants/clients/stores) pelos models configurados...');
        $this->migrateDatabasesFromConfig($fresh, $seed, $selectedTypes);

        $this->newLine();
        $this->info('✅ Todas as migrations foram executadas com sucesso!');

        return self::SUCCESS;
    }

    /**
     * Migra o banco principal (conexão default) com migrations da pasta principal
     */
    protected function migratePrincipalDatabase(bool $fresh, bool $seed): bool
    {
        $paths = config('raptor.migrations.default', 'database/migrations');
        $paths = is_array($paths) ? $paths : [$paths];
        $this->info("   → Banco: {$this->defaultConnection}");
        $this->info('   → Pasta(s): '.implode(', ', $paths));

        try {
            $baseOptions = [
                '--database' => $this->defaultConnection,
                '--realpath' => false,
            ];

            $first = true;
            foreach ($paths as $path) {
                if ($fresh && $first) {
                    $this->call('migrate:fresh', array_merge($baseOptions, ['--path' => $path]));
                    $first = false;
                } else {
                    $this->call('migrate', array_merge($baseOptions, ['--path' => $path]));
                }
            }

            if ($seed) {
                $this->call('db:seed', ['--database' => $this->defaultConnection]);
            }

            $this->info('   ✅ Banco principal migrado com sucesso!');

            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Erro ao migrar banco principal: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Resolve bancos a partir dos models configurados e roda as migrations em seus paths.
     */
    protected function migrateDatabasesFromConfig(bool $fresh, bool $seed, array $selectedTypes = []): void
    {
        $databases = $this->collectDatabasesFromModels($selectedTypes);
       
        if (empty($databases)) {
            $message = '   Nenhum banco encontrado nos models configurados.';
            if ($selectedTypes !== []) {
                $message = sprintf(
                    '   Nenhum banco encontrado nos models configurados para os tipos: %s.',
                    implode(', ', $selectedTypes)
                );
            }

            $this->warn($message);

            return;
        }

        $this->info(sprintf('   Encontrados %d banco(s) para migrar:', count($databases)));
        $this->newLine();

        $this->table(
            ['Tipo', 'Nome', 'Banco de Dados'],
            collect($databases)->map(fn ($db) => [$db['type'], $db['name'], $db['database']])->toArray()
        );

        $this->newLine();

        $successCount = 0;
        $errorCount = 0;

        foreach ($databases as $db) {
            $result = $this->runMigrationsOnDatabase($db, $fresh, $seed);
            $result ? $successCount++ : $errorCount++;
        }

        $this->newLine();
        $this->table(
            ['Status', 'Quantidade'],
            [
                ['✅ Sucesso', $successCount],
                ['❌ Erro', $errorCount],
            ]
        );
    }

    /**
     * Monta a lista de bancos a migrar a partir dos models tenant/client/store configurados.
     * Mantém database_models apenas como fallback para tipos customizados adicionais.
     *
     * @return array<int, array{type: string, name: string, database: string, paths: array<int, string>}>
     */
    protected function collectDatabasesFromModels(array $selectedTypes = []): array
    {
        $selectedTypes = array_map('strtolower', $selectedTypes);
        $standardEntries = $this->getStandardMigrationEntries();
        $customEntries = $this->getCustomMigrationEntries(['tenant', 'client', 'store']);
        $hasHierarchyEntries = $standardEntries !== [];

        $list = [];
        $seen = [];
        $processedTypes = [];

        if ($hasHierarchyEntries) {
            $tenantEntry = $standardEntries['tenant'] ?? null;
            $clientEntry = $standardEntries['client'] ?? null;
            $storeEntry = $standardEntries['store'] ?? null;

            $tenantClass = $this->resolveModelClass($tenantEntry['model'] ?? null);
            $clientClass = $this->resolveModelClass($clientEntry['model'] ?? null);
            $storeClass = $this->resolveModelClass($storeEntry['model'] ?? null);

            $tenants = $this->queryRecordsForMigration($tenantClass)->keyBy(fn ($record) => (string) $record->getKey());
            $clients = $this->queryRecordsForMigration($clientClass);
            $stores = $this->queryRecordsForMigration($storeClass);

            $clientsByTenant = $clients->groupBy(fn ($record) => (string) ($record->tenant_id ?? ''));
            $storesByClient = $stores->groupBy(fn ($record) => (string) ($record->client_id ?? ''));

            foreach ($tenants as $tenant) {
                if ($tenantEntry && $this->shouldIncludeType('tenant', $selectedTypes)) {
                    $database = $this->resolveDatabaseName(null, null, $tenant);
                    $this->appendMigrationTarget($list, $seen, [
                        'type' => $tenantEntry['type'] ?? 'Tenant',
                        'name' => $tenant->{$tenantEntry['name_key'] ?? 'name'} ?? (string) $tenant->getKey(),
                        'database' => $database,
                        'paths' => $tenantEntry['paths'] ?? [],
                    ]);
                    $processedTypes['tenant'] = true;
                }

                foreach ($clientsByTenant->get((string) $tenant->getKey(), collect()) as $client) {
                    if ($clientEntry && $this->shouldIncludeType('client', $selectedTypes)) {
                        $this->appendClientMigrationTargets(
                            $list,
                            $seen,
                            $clientEntry,
                            $client,
                            $tenant,
                            $storesByClient->get((string) $client->getKey(), collect())
                        );
                        $processedTypes['client'] = true;
                    }

                    foreach ($storesByClient->get((string) $client->getKey(), collect()) as $store) {
                        if ($storeEntry && $this->shouldIncludeType('store', $selectedTypes)) {
                            $database = $this->resolveDatabaseName($store, $client, $tenant);
                            $this->appendMigrationTarget($list, $seen, [
                                'type' => $storeEntry['type'] ?? 'Store',
                                'name' => $store->{$storeEntry['name_key'] ?? 'name'} ?? (string) $store->getKey(),
                                'database' => $database,
                                'paths' => $storeEntry['paths'] ?? [],
                            ]);
                            $processedTypes['store'] = true;
                        }
                    }
                }
            }

            foreach ($clients->filter(fn ($record) => blank($record->tenant_id)) as $client) {
                if ($clientEntry && $this->shouldIncludeType('client', $selectedTypes)) {
                    $this->appendClientMigrationTargets(
                        $list,
                        $seen,
                        $clientEntry,
                        $client,
                        null,
                        $storesByClient->get((string) $client->getKey(), collect())
                    );
                    $processedTypes['client'] = true;
                }

                foreach ($storesByClient->get((string) $client->getKey(), collect()) as $store) {
                    if ($storeEntry && $this->shouldIncludeType('store', $selectedTypes)) {
                        $database = $this->resolveDatabaseName($store, $client, null);
                        $this->appendMigrationTarget($list, $seen, [
                            'type' => $storeEntry['type'] ?? 'Store',
                            'name' => $store->{$storeEntry['name_key'] ?? 'name'} ?? (string) $store->getKey(),
                            'database' => $database,
                            'paths' => $storeEntry['paths'] ?? [],
                        ]);
                        $processedTypes['store'] = true;
                    }
                }
            }

            foreach ($stores->filter(fn ($record) => blank($record->client_id)) as $store) {
                if ($storeEntry && $this->shouldIncludeType('store', $selectedTypes)) {
                    $database = $this->resolveDatabaseName($store, null, null);
                    $this->appendMigrationTarget($list, $seen, [
                        'type' => $storeEntry['type'] ?? 'Store',
                        'name' => $store->{$storeEntry['name_key'] ?? 'name'} ?? (string) $store->getKey(),
                        'database' => $database,
                        'paths' => $storeEntry['paths'] ?? [],
                    ]);
                    $processedTypes['store'] = true;
                }
            }
        }

        foreach ($customEntries as $entry) {
            $paths = $entry['paths'] ?? [];
            if (empty($paths) || ! is_array($paths)) {
                continue;
            }

            $type = strtolower((string) ($entry['type'] ?? ''));
            if ($type !== '' && isset($processedTypes[$type])) {
                continue;
            }

            if ($selectedTypes !== [] && ! in_array($type, $selectedTypes, true)) {
                continue;
            }

            $modelClass = $this->resolveModelClass($entry['model'] ?? null);
            if (! $modelClass || ! class_exists($modelClass)) {
                continue;
            }

            $nameKey = $entry['name_key'] ?? 'name';

            foreach ($this->queryRecordsForMigration($modelClass) as $record) {
                $this->appendMigrationTarget($list, $seen, [
                    'type' => $entry['type'] ?? class_basename($modelClass),
                    'name' => $record->{$nameKey} ?? (string) $record->getKey(),
                    'database' => $record->database ?? null,
                    'paths' => $paths,
                ]);
            }
        }

        return $list;
    }

    /**
     * @return array<string, array{model: string, type: string, name_key: string, paths: array<int, string>}>
     */
    protected function getStandardMigrationEntries(): array
    {
        $entries = [
            'tenant' => [
                'model' => config('raptor.migrations.models.tenant', config('raptor.landlord.models.tenant')),
                'type' => 'Tenant',
                'name_key' => 'name',
                'paths' => $this->normalizePaths([
                    config('raptor.migrations.default'),
                    config('raptor.migrations.tenant'),
                ]),
            ],
            'client' => [
                'model' => config('raptor.migrations.models.client'),
                'type' => 'Client',
                'name_key' => 'name',
                'paths' => $this->normalizePaths([
                    config('raptor.migrations.client'),
                ]),
            ],
            'store' => [
                'model' => config('raptor.migrations.models.store'),
                'type' => 'Store',
                'name_key' => 'name',
                'paths' => $this->normalizePaths([
                    config('raptor.migrations.default'),
                    config('raptor.migrations.store'),
                ]),
            ],
        ];

        $resolved = [];

        foreach ($entries as $type => $entry) {
            $modelClass = $this->resolveModelClass($entry['model'] ?? null);
            if (! is_string($modelClass) || $modelClass === '' || ($entry['paths'] ?? []) === []) {
                continue;
            }

            $entry['model'] = $modelClass;
            $resolved[$type] = $entry;
        }

        return $resolved;
    }

    /**
     * @param  array<int, string>  $reservedTypes
     * @return array<int, array{model: string, type: string, name_key?: string, paths: array<int, string>}>
     */
    protected function getCustomMigrationEntries(array $reservedTypes = []): array
    {
        $reservedTypes = array_map('strtolower', $reservedTypes);
        $entries = [];

        foreach (config('raptor.migrations.database_models', []) as $entry) {
            $type = strtolower((string) ($entry['type'] ?? ''));
            if ($type === '' || in_array($type, $reservedTypes, true)) {
                continue;
            }

            $paths = $this->normalizePaths($entry['paths'] ?? []);
            $modelClass = $this->resolveModelClass($entry['model'] ?? null);

            if (! is_string($modelClass) || $modelClass === '' || $paths === []) {
                continue;
            }

            $entry['model'] = $modelClass;
            $entry['paths'] = $paths;
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * @param  array<int, mixed>|mixed  $paths
     * @return array<int, string>
     */
    protected function normalizePaths($paths): array
    {
        $paths = is_array($paths) ? $paths : [$paths];

        return collect($paths)
            ->flatten()
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->map(fn (string $path) => trim($path))
            ->unique()
            ->values()
            ->all();
    }

    protected function shouldIncludeType(string $type, array $selectedTypes): bool
    {
        return $selectedTypes === [] || in_array($type, $selectedTypes, true);
    }

    protected function queryRecordsForMigration(?string $modelClass)
    {
        if (! $modelClass || ! class_exists($modelClass)) {
            return collect();
        }

        $model = new $modelClass;
        $query = $modelClass::query()->withoutGlobalScopes();

        if (method_exists($query, 'withoutEagerLoads')) {
            $query->withoutEagerLoads();
        }

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)
            && method_exists($model, 'getQualifiedDeletedAtColumn')) {
            $query->whereNull($model->getQualifiedDeletedAtColumn());
        }

        return $query->get();
    }

    protected function appendClientMigrationTargets(
        array &$list,
        array &$seen,
        array $clientEntry,
        object $client,
        ?object $tenant,
        $stores
    ): void {
        $clientName = $client->{$clientEntry['name_key'] ?? 'name'} ?? (string) $client->getKey();
        $paths = $clientEntry['paths'] ?? [];

        $storeTargets = collect($stores)
            ->map(function ($store) use ($clientEntry, $clientName, $paths) {
                $database = $this->resolveDatabaseName($store, null, null);
                if ($database === null) {
                    return null;
                }

                $storeName = $store->{$clientEntry['name_key'] ?? 'name'} ?? (string) $store->getKey();

                return [
                    'type' => $clientEntry['type'] ?? 'Client',
                    'name' => sprintf('%s → %s', $clientName, $storeName),
                    'database' => $database,
                    'paths' => $paths,
                ];
            })
            ->filter()
            ->unique(fn ($target) => ($target['database'] ?? '').'|'.implode(',', $target['paths'] ?? []))
            ->values();

        if ($storeTargets->isNotEmpty()) {
            foreach ($storeTargets as $target) {
                $this->appendMigrationTarget($list, $seen, $target);
            }

            return;
        }

        $this->appendMigrationTarget($list, $seen, [
            'type' => $clientEntry['type'] ?? 'Client',
            'name' => $clientName,
            'database' => $this->resolveDatabaseName(null, $client, $tenant),
            'paths' => $paths,
        ]);
    }

    protected function resolveDatabaseName($store = null, $client = null, $tenant = null): ?string
    {
        foreach ([$store, $client, $tenant] as $record) {
            $database = is_object($record) ? trim((string) ($record->database ?? '')) : '';
            if ($database !== '') {
                return $database;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{type: string, name: string, database: string, paths: array<int, string>}>  $list
     * @param  array<string, bool>  $seen
     * @param  array{type: string, name: string, database: ?string, paths: array<int, string>}  $target
     */
    protected function appendMigrationTarget(array &$list, array &$seen, array $target): void
    {
        $database = trim((string) ($target['database'] ?? ''));
        $paths = array_values(array_filter($target['paths'] ?? []));

        if ($database === '' || $paths === []) {
            return;
        }

        $key = strtolower($target['type']).'|'.$database.'|'.implode(',', $paths);
        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $target['database'] = $database;
        $target['paths'] = $paths;
        $list[] = $target;
    }

    /**
     * @return array<int, string>
     */
    protected function parseSelectedTypes(): array
    {
        $value = $this->option('type');
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($type) => strtolower(trim($type)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve a classe do model: se for chave de config (ex: raptor.landlord.models.tenant), usa config(); senão assume que é FQCN.
     */
    protected function resolveModelClass(?string $model): ?string
    {
        if ($model === null || $model === '') {
            return null;
        }

        if (str_starts_with($model, 'raptor.') || str_starts_with($model, 'config.')) {
            $resolved = config(str_starts_with($model, 'config.') ? $model : $model);

            return is_string($resolved) ? $resolved : null;
        }

        return $model;
    }

    /**
     * Roda as migrations nos paths configurados para um banco. Não cria nem apaga banco.
     *
     * @param  array{type: string, name: string, database: string, paths: array<int, string>}  $db
     */
    protected function runMigrationsOnDatabase(array $db, bool $fresh, bool $seed): bool
    {
        $database = $db['database'];
        $type = $db['type'];
        $name = $db['name'];
        $paths = $db['paths'];

        $this->info("   🔄 Migrando [{$type}] {$name} → {$database}");

        try {
            $this->setupTenantConnection($database);

            if (! $this->databaseExists($database)) {
                $this->warn("      ⚠️  Banco '{$database}' não existe. Não criamos banco aqui — pule.");

                return false;
            }

            $mainOptions = ['--database' => config('database.default'), '--realpath' => false];
            $first = true;

            foreach ($paths as $path) {
                $fullPath = base_path($path);
                if (! is_dir($fullPath)) {
                    $fullPath = $path;
                }
                if (! is_dir($fullPath)) {
                    $this->warn("      Pasta não encontrada: {$path}");

                    continue;
                }
                $this->info("      📁 Migrando {$path}...");
                $opts = array_merge($mainOptions, ['--path' => $path]);
                if ($fresh && $first) {
                    Artisan::call('migrate:fresh', $opts);
                    $first = false;
                } else {
                    Artisan::call('migrate', $opts);
                }
            }

            if ($seed) {
                $this->info('      🌱 Executando seeders...');
                Artisan::call('db:seed', ['--database' => config('database.default')]);
            }

            $this->info('      ✅ Migrado com sucesso!');

            return true;

        } catch (\Exception $e) {
            $this->error("      ❌ Erro: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Aponta a conexão do tenant (ex.: default) para o banco informado.
     */
    protected function setupTenantConnection(string $database): void
    {
        $connectionName = config('database.default');
        Config::set("database.connections.{$connectionName}.database", $database);
        DB::purge($connectionName);
    }

    /**
     * Verifica se o banco de dados existe
     */
    protected function databaseExists(string $database): bool
    {
        try {
            $driver = config("database.connections.{$this->defaultConnection}.driver");

            if ($driver === 'pgsql') {
                $result = DB::connection($this->defaultConnection)
                    ->select('SELECT 1 FROM pg_database WHERE datname = ?', [$database]);
            } else {
                $result = DB::connection($this->defaultConnection)
                    ->select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$database]);
            }

            return count($result) > 0;

        } catch (\Exception $e) {
            return false;
        }
    }
}
