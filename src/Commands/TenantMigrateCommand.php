<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use App\Models\Client;
use App\Models\Store;
use Callcocam\LaravelRaptor\Models\Tenant;
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

    protected $description = 'Executa migrations no banco principal e nos bancos resolvidos pelas tabelas tenants/clients/stores';

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

        // PASSO 2: Resolver bancos a partir das tabelas tenants/clients/stores e rodar migrations nos paths fixos
        $this->newLine();
        $this->info('📦 PASSO 2: Migrando bancos (tenants/clients/stores) pelas tabelas...');
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
    * Resolve bancos a partir das tabelas tenants/clients/stores e roda migrations em paths fixos.
     */
    protected function migrateDatabasesFromConfig(bool $fresh, bool $seed, array $selectedTypes = []): void
    {
        $databases = $this->collectDatabasesFromModels($selectedTypes);
       
        if (empty($databases)) {
            $message = '   Nenhum banco encontrado nas tabelas tenants/clients/stores.';
            if ($selectedTypes !== []) {
                $message = sprintf(
                    '   Nenhum banco encontrado nas tabelas tenants/clients/stores para os tipos: %s.',
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
    * Monta a lista de bancos a migrar a partir das tabelas tenants/clients/stores.
     *
     * @return array<int, array{type: string, name: string, database: string, paths: array<int, string>}>
     */
    protected function collectDatabasesFromModels(array $selectedTypes = []): array
    {
        $selectedTypes = array_map('strtolower', $selectedTypes);
        $list = [];
        $seen = [];
        $clientPaths = $this->normalizePaths(['database/migrations/clients']);
        $storePaths = $this->normalizePaths(['database/migrations/clients/stores']);

        $tenants = $this->queryRecordsForMigration(Tenant::class)->keyBy(fn ($record) => (string) $record->getKey());
        $clients = $this->queryRecordsForMigration(Client::class);
        $clientsByTenant = $clients->groupBy(fn ($record) => (string) ($record->tenant_id ?? ''));
        $stores = $this->queryRecordsForMigration(Store::class);
        $storesByClient = $stores->groupBy(fn ($record) => (string) ($record->client_id ?? ''));
        $processedClientIds = [];
        $processedStoreIds = [];

        foreach ($tenants as $tenant) {
            $tenantClients = $clientsByTenant->get((string) $tenant->getKey(), collect());

            if ($tenantClients->isEmpty()) {
                continue;
            }

            foreach ($tenantClients as $client) {
                $processedClientIds[(string) $client->getKey()] = true;
                $clientStores = $storesByClient->get((string) $client->getKey(), collect());
                $storesWithDatabase = $clientStores->filter(function ($store) {
                    return trim((string) ($store->database ?? '')) !== '';
                });

                if ($storesWithDatabase->isNotEmpty()) {
                    if ($this->shouldIncludeType('store', $selectedTypes)) {
                        foreach ($storesWithDatabase as $store) {
                            $processedStoreIds[(string) $store->getKey()] = true;
                            $this->appendMigrationTarget($list, $seen, [
                                'type' => 'Store',
                                'name' => $store->name ?? (string) $store->getKey(),
                                'database' => $this->resolveDatabaseName($store),
                                'paths' => $storePaths,
                            ]);
                        }
                    }

                    continue;
                }

                if ($this->shouldIncludeType('client', $selectedTypes)) {
                    $this->appendMigrationTarget($list, $seen, [
                        'type' => 'Client',
                        'name' => $client->name ?? (string) $client->getKey(),
                        'database' => $this->resolveDatabaseName(null, $client),
                        'paths' => $clientPaths,
                    ]);
                }
            }
        }

        $orphanClients = $clients->filter(function ($client) use ($processedClientIds) {
            return ! isset($processedClientIds[(string) $client->getKey()]);
        });

        foreach ($orphanClients as $client) {
            $processedClientIds[(string) $client->getKey()] = true;

            $clientStores = $storesByClient->get((string) $client->getKey(), collect());
            $storesWithDatabase = $clientStores->filter(function ($store) {
                return trim((string) ($store->database ?? '')) !== '';
            });

            if ($storesWithDatabase->isNotEmpty()) {
                if ($this->shouldIncludeType('store', $selectedTypes)) {
                    foreach ($storesWithDatabase as $store) {
                        $processedStoreIds[(string) $store->getKey()] = true;
                        $this->appendMigrationTarget($list, $seen, [
                            'type' => 'Store',
                            'name' => $store->name ?? (string) $store->getKey(),
                            'database' => $this->resolveDatabaseName($store),
                            'paths' => $storePaths,
                        ]);
                    }
                }

                continue;
            }

            if ($this->shouldIncludeType('client', $selectedTypes)) {
                $this->appendMigrationTarget($list, $seen, [
                    'type' => 'Client',
                    'name' => $client->name ?? (string) $client->getKey(),
                    'database' => $this->resolveDatabaseName(null, $client),
                    'paths' => $clientPaths,
                ]);
            }
        }

        $orphanStores = $stores->filter(function ($store) use ($processedStoreIds) {
            return ! isset($processedStoreIds[(string) $store->getKey()]);
        });

        if ($this->shouldIncludeType('store', $selectedTypes)) {
            foreach ($orphanStores as $store) {
                $this->appendMigrationTarget($list, $seen, [
                    'type' => 'Store',
                    'name' => $store->name ?? (string) $store->getKey(),
                    'database' => $this->resolveDatabaseName($store),
                    'paths' => $storePaths,
                ]);
            }
        }

        return $list;
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
