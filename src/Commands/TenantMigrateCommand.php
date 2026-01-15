<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'raptor:migrate-tenants 
                            {--force : Força a execução sem confirmação}
                            {--dry-run : Apenas mostra o que seria executado, sem executar}
                            {--type= : Tipo específico (tenant, client, store)}
                            {--database= : Nome específico do banco de dados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa migrations em todos os bancos de dados de tenants, clients e stores';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Iniciando execução de migrations em múltiplos bancos...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $typeFilter = $this->option('type');
        $databaseFilter = $this->option('database');

        if ($dryRun) {
            $this->warn('⚠️  Modo DRY-RUN ativado. Nenhuma alteração será feita.');
            $this->newLine();
        }

        if (!$force && !$dryRun && !$this->confirm('Deseja continuar? Isso executará migrations em múltiplos bancos de dados.', false)) {
            $this->warn('Operação cancelada.');
            return self::FAILURE;
        }

        // Busca registros com database preenchido
        $items = $this->getItemsWithDatabase($typeFilter, $databaseFilter);

        if (empty($items)) {
            $this->warn('Nenhum registro encontrado com banco de dados configurado.');
            return self::SUCCESS;
        }

        $this->info(sprintf('📊 Encontrados %d registro(s) com banco de dados configurado:', count($items)));
        $this->newLine();

        // Exibe lista
        $this->displayItemsList($items);

        if (!$force && !$dryRun && !$this->confirm('Deseja executar migrations nestes bancos?', true)) {
            $this->warn('Operação cancelada.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('🔄 Iniciando execução de migrations...');
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($items as $item) {
            $result = $this->migrateDatabase($item, $dryRun);
            
            switch ($result['status']) {
                case 'success':
                    $successCount++;
                    break;
                case 'error':
                    $errorCount++;
                    break;
                case 'skipped':
                    $skippedCount++;
                    break;
            }
        }

        $this->newLine();
        $this->info('✅ Execução concluída!');
        $this->table(
            ['Status', 'Quantidade'],
            [
                ['✅ Sucesso', $successCount],
                ['❌ Erro', $errorCount],
                ['⏭️  Ignorados', $skippedCount],
            ]
        );

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Busca registros (tenants, clients, stores) com database preenchido
     */
    protected function getItemsWithDatabase(?string $typeFilter = null, ?string $databaseFilter = null): array
    {
        $items = [];

        // Busca Tenants
        if (!$typeFilter || $typeFilter === 'tenant') {
            $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
            $tenants = $tenantModel::whereNotNull('database')
                ->where('database', '!=', '')
                ->when($databaseFilter, fn($q) => $q->where('database', $databaseFilter))
                ->get();

            foreach ($tenants as $tenant) {
                $items[] = [
                    'type' => 'tenant',
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'database' => $tenant->database,
                    'model' => $tenant,
                ];
            }
        }

        // Busca Clients (se o modelo existir e tiver campo database)
        if (!$typeFilter || $typeFilter === 'client') {
            $clientModel = config('raptor.migrations.models.client', 'App\\Models\\Client');
            if (class_exists($clientModel) && $this->hasDatabaseColumn($clientModel, 'clients')) {
                $clients = $clientModel::whereNotNull('database')
                    ->where('database', '!=', '')
                    ->when($databaseFilter, fn($q) => $q->where('database', $databaseFilter))
                    ->get();

                foreach ($clients as $client) {
                    $items[] = [
                        'type' => 'client',
                        'id' => $client->id,
                        'name' => $client->name ?? $client->id,
                        'database' => $client->database,
                        'model' => $client,
                    ];
                }
            }
        }

        // Busca Stores (se o modelo existir e tiver campo database)
        if (!$typeFilter || $typeFilter === 'store') {
            $storeModel = config('raptor.migrations.models.store', 'App\\Models\\Store');
            if (class_exists($storeModel) && $this->hasDatabaseColumn($storeModel, 'stores')) {
                $stores = $storeModel::whereNotNull('database')
                    ->where('database', '!=', '')
                    ->when($databaseFilter, fn($q) => $q->where('database', $databaseFilter))
                    ->get();

                foreach ($stores as $store) {
                    $items[] = [
                        'type' => 'store',
                        'id' => $store->id,
                        'name' => $store->name ?? $store->id,
                        'database' => $store->database,
                        'model' => $store,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Verifica se a tabela tem a coluna database
     */
    protected function hasDatabaseColumn(string $modelClass, string $tableName): bool
    {
        try {
            return Schema::hasColumn($tableName, 'database');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Exibe lista de itens encontrados
     */
    protected function displayItemsList(array $items): void
    {
        $tableData = [];
        foreach ($items as $item) {
            $tableData[] = [
                Str::ucfirst($item['type']),
                $item['name'],
                $item['database'],
                $item['id'],
            ];
        }

        $this->table(
            ['Tipo', 'Nome', 'Database', 'ID'],
            $tableData
        );
    }

    /**
     * Executa migrations em um banco de dados específico
     */
    protected function migrateDatabase(array $item, bool $dryRun = false): array
    {
        $type = $item['type'];
        $name = $item['name'];
        $database = $item['database'];
        $connectionName = 'tenant_migrate_' . Str::random(8);

        $this->line(sprintf('📦 Processando %s: <info>%s</info> (DB: <comment>%s</comment>)', 
            Str::ucfirst($type), 
            $name, 
            $database
        ));

        try {
            // Cria conexão temporária
            if (!$dryRun) {
                $this->createTemporaryConnection($connectionName, $database);
            } else {
                $this->comment('   [DRY-RUN] Conexão seria criada para: ' . $database);
            }

            // Verifica se banco existe, se não, cria
            if (!$dryRun) {
                if (!$this->databaseExists($connectionName, $database)) {
                    $this->warn('   ⚠️  Banco de dados não existe. Criando...');
                    $this->createDatabase($connectionName, $database);
                    $this->info('   ✅ Banco de dados criado com sucesso!');
                }
            } else {
                $this->comment('   [DRY-RUN] Verificação/criação de banco seria executada');
            }

            // Obtém migrations a executar
            $migrations = $this->getMigrationsForType($type);

            if (empty($migrations)) {
                $this->warn('   ⚠️  Nenhuma migration configurada para este tipo.');
                return ['status' => 'skipped', 'message' => 'Nenhuma migration configurada'];
            }

            if ($dryRun) {
                $this->comment(sprintf('   [DRY-RUN] %d migration(s) seriam executadas:', count($migrations)));
                foreach ($migrations as $migration) {
                    $this->comment('      - ' . $migration);
                }
                return ['status' => 'success', 'message' => 'Dry-run executado'];
            }

            // Executa migrations
            $this->info(sprintf('   🔄 Executando %d migration(s)...', count($migrations)));
            $this->runMigrations($connectionName, $migrations);

            $this->info('   ✅ Migrations executadas com sucesso!');

            // Remove conexão temporária
            $this->removeTemporaryConnection($connectionName);

            return ['status' => 'success', 'message' => 'Migrations executadas'];

        } catch (\Exception $e) {
            $this->error('   ❌ Erro: ' . $e->getMessage());
            
            // Remove conexão temporária em caso de erro
            if (!$dryRun) {
                $this->removeTemporaryConnection($connectionName);
            }

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Cria conexão temporária para o banco
     */
    protected function createTemporaryConnection(string $connectionName, string $database): void
    {
        $defaultConnection = Config::get('database.default');
        $defaultConfig = Config::get("database.connections.{$defaultConnection}", []);

        $tempConfig = array_merge($defaultConfig, [
            'database' => $database,
        ]);

        Config::set("database.connections.{$connectionName}", $tempConfig);
    }

    /**
     * Remove conexão temporária
     */
    protected function removeTemporaryConnection(string $connectionName): void
    {
        $connections = Config::get('database.connections', []);
        unset($connections[$connectionName]);
        Config::set('database.connections', $connections);
    }

    /**
     * Verifica se o banco de dados existe
     */
    protected function databaseExists(string $connectionName, string $database): bool
    {
        try {
            // Tenta conectar sem especificar o database
            $defaultConnection = Config::get('database.default');
            $defaultConfig = Config::get("database.connections.{$defaultConnection}", []);
            
            // Cria conexão temporária sem database para verificar se existe
            $checkConfig = array_merge($defaultConfig, ['database' => null]);
            $checkConnectionName = 'check_' . Str::random(8);
            Config::set("database.connections.{$checkConnectionName}", $checkConfig);

            $driver = $defaultConfig['driver'] ?? 'mysql';
            
            if ($driver === 'mysql') {
                $result = DB::connection($checkConnectionName)
                    ->select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$database]);
                $exists = !empty($result);
            } elseif ($driver === 'pgsql') {
                $result = DB::connection($checkConnectionName)
                    ->select("SELECT 1 FROM pg_database WHERE datname = ?", [$database]);
                $exists = !empty($result);
            } else {
                // Para outros drivers, tenta conectar diretamente
                try {
                    DB::connection($connectionName)->getPdo();
                    $exists = true;
                } catch (\Exception $e) {
                    $exists = false;
                }
            }

            // Remove conexão de verificação
            $connections = Config::get('database.connections', []);
            unset($connections[$checkConnectionName]);
            Config::set('database.connections', $connections);

            return $exists;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Cria o banco de dados se não existir
     */
    protected function createDatabase(string $connectionName, string $database): void
    {
        $defaultConnection = Config::get('database.default');
        $defaultConfig = Config::get("database.connections.{$defaultConnection}", []);
        $driver = $defaultConfig['driver'] ?? 'mysql';

        // Cria conexão sem database para criar o banco
        $createConfig = array_merge($defaultConfig, ['database' => null]);
        $createConnectionName = 'create_' . Str::random(8);
        Config::set("database.connections.{$createConnectionName}", $createConfig);

        try {
            if ($driver === 'mysql') {
                $charset = $defaultConfig['charset'] ?? 'utf8mb4';
                $collation = $defaultConfig['collation'] ?? 'utf8mb4_unicode_ci';
                DB::connection($createConnectionName)
                    ->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$collation}");
            } elseif ($driver === 'pgsql') {
                DB::connection($createConnectionName)
                    ->statement("CREATE DATABASE \"{$database}\"");
            } else {
                throw new \Exception("Driver {$driver} não suporta criação automática de banco de dados");
            }
        } finally {
            // Remove conexão de criação
            $connections = Config::get('database.connections', []);
            unset($connections[$createConnectionName]);
            Config::set('database.connections', $connections);
        }
    }

    /**
     * Obtém migrations configuradas para o tipo
     */
    protected function getMigrationsForType(string $type): array
    {
        $config = config('raptor.migrations', []);
        
        $migrations = [];
        
        // Adiciona migrations padrões
        if (isset($config['default']) && is_array($config['default'])) {
            $migrations = array_merge($migrations, $config['default']);
        }
        
        // Adiciona migrations específicas do tipo
        if (isset($config[$type]) && is_array($config[$type])) {
            $migrations = array_merge($migrations, $config[$type]);
        }

        return array_unique($migrations);
    }

    /**
     * Executa as migrations
     */
    protected function runMigrations(string $connectionName, array $migrationFiles): void
    {
        $migrationsPath = database_path('migrations');
        $force = config('raptor.migrations.options.force', false);

        foreach ($migrationFiles as $migrationFile) {
            $migrationPath = $migrationsPath . '/' . $migrationFile;
            
            if (!file_exists($migrationPath)) {
                $this->warn("   ⚠️  Migration não encontrada: {$migrationFile}");
                continue;
            }

            // Verifica se já foi executada (se não for force)
            if (!$force && $this->migrationAlreadyRun($connectionName, $migrationFile)) {
                $this->comment("   ⏭️  Migration já executada: {$migrationFile}");
                continue;
            }

            $this->line("   🔄 Executando: {$migrationFile}");
            
            try {
                // Carrega a migration e obtém a instância (suporta classes anônimas)
                $migration = $this->loadMigration($migrationPath, $migrationFile);
                
                if (!$migration) {
                    $this->warn("   ⚠️  Não foi possível carregar a migration: {$migrationFile}");
                    continue;
                }
                
                // Executa a migration usando a conexão específica
                DB::connection($connectionName)->transaction(function () use ($migration, $connectionName) {
                    // Salva conexão padrão
                    $originalDefault = Config::get('database.default');
                    
                    try {
                        // Define conexão para a migration
                        if (method_exists($migration, 'setConnection')) {
                            $migration->setConnection($connectionName);
                        }
                        
                        // Muda conexão padrão temporariamente
                        Config::set('database.default', $connectionName);
                        
                        // Executa migration
                        $migration->up();
                    } finally {
                        // Restaura conexão padrão
                        Config::set('database.default', $originalDefault);
                    }
                });

                // Registra migration como executada
                $this->recordMigration($connectionName, $migrationFile);
                
                $this->info("   ✅ Migration executada: {$migrationFile}");
            } catch (\Exception $e) {
                $this->error("   ❌ Erro ao executar {$migrationFile}: " . $e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Carrega a migration e retorna a instância (suporta classes anônimas e nomeadas)
     */
    protected function loadMigration(string $migrationPath, string $migrationFile)
    {
        // Carrega o arquivo (pode retornar uma classe anônima ou apenas carregar a classe)
        $result = require $migrationPath;
        
        // Se retornou uma instância de Migration (classe anônima), usa ela
        if ($result instanceof \Illuminate\Database\Migrations\Migration) {
            return $result;
        }
        
        // Se não retornou uma instância, tenta encontrar classe nomeada
        // Primeiro tenta pelo nome inferido do arquivo
        $className = $this->getMigrationClassName($migrationFile);
        if (class_exists($className)) {
            return new $className();
        }
        
        // Se não encontrou, tenta encontrar qualquer classe Migration no arquivo
        $fileContent = file_get_contents($migrationPath);
        
        // Procura por "class Nome extends Migration"
        if (preg_match('/class\s+(\w+)\s+extends\s+Migration/', $fileContent, $matches)) {
            $foundClassName = $matches[1];
            if (class_exists($foundClassName)) {
                return new $foundClassName();
            }
        }
        
        // Se ainda não encontrou, tenta procurar por "return new class extends Migration"
        // Nesse caso, o require já deve ter retornado a instância
        // Mas se não retornou, significa que é uma classe nomeada que não foi encontrada
        return null;
    }

    /**
     * Obtém o nome da classe da migration a partir do arquivo
     */
    protected function getMigrationClassName(string $migrationFile): string
    {
        // Remove extensão .php
        $name = str_replace('.php', '', $migrationFile);
        
        // Converte para nome de classe (ex: 2024_01_01_000000_create_users_table -> CreateUsersTable)
        $parts = explode('_', $name);
        $parts = array_slice($parts, 4); // Remove data e hora
        $className = implode('', array_map('ucfirst', $parts));
        
        return $className;
    }

    /**
     * Verifica se a migration já foi executada
     */
    protected function migrationAlreadyRun(string $connectionName, string $migrationFile): bool
    {
        try {
            // Verifica se a tabela migrations existe
            if (!Schema::connection($connectionName)->hasTable('migrations')) {
                return false;
            }

            $migration = DB::connection($connectionName)
                ->table('migrations')
                ->where('migration', str_replace('.php', '', $migrationFile))
                ->exists();

            return $migration;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Registra migration como executada
     */
    protected function recordMigration(string $connectionName, string $migrationFile): void
    {
        try {
            // Garante que a tabela migrations existe
            if (!Schema::connection($connectionName)->hasTable('migrations')) {
                Schema::connection($connectionName)->create('migrations', function ($table) {
                    $table->id();
                    $table->string('migration');
                    $table->integer('batch');
                });
            }

            $batch = DB::connection($connectionName)
                ->table('migrations')
                ->max('batch') ?? 0;

            DB::connection($connectionName)
                ->table('migrations')
                ->insert([
                    'migration' => str_replace('.php', '', $migrationFile),
                    'batch' => $batch + 1,
                ]);
        } catch (\Exception $e) {
            // Ignora erros ao registrar (pode ser que já exista)
        }
    }
}

