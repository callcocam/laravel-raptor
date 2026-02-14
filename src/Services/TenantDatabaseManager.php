<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Support\ResolvedTenantConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantDatabaseManager
{
    protected string $defaultConnection;

    public function __construct()
    {
        $this->defaultConnection = config('database.default');
    }

    /**
     * Retorna o nome do banco da conexão default (env).
     */
    public function getDefaultDatabaseName(): string
    {
        return (string) config("database.connections.{$this->defaultConnection}.database");
    }

    /**
     * Aponta a conexão default para o banco informado (banco do tenant).
     */
    public function setupConnection(string $database): void
    {
        $this->switchConnectionTo($this->defaultConnection, $database);
    }

    /**
     * Altera o banco de uma conexão para o informado.
     * Models que usam essa conexão passam a usar o banco do tenant.
     */
    public function switchConnectionTo(string $connectionName, string $database): void
    {
        Config::set("database.connections.{$connectionName}.database", $database);
        DB::purge($connectionName);
    }

    /**
     * Altera o banco da conexão default para o informado.
     */
    public function switchDefaultConnectionTo(string $database): void
    {
        $this->switchConnectionTo($this->defaultConnection, $database);
    }

    /**
     * Aplica a configuração resolvida do tenant: altera a conexão (default ou config) para o banco do tenant.
     */
    public function applyConfig(ResolvedTenantConfig $config): void
    {
        if (! $config->hasDedicatedDatabase()) {
            return;
        }

        $database = (string) $config->database;
        $this->switchConnectionTo($config->connectionName ?? $this->defaultConnection, $database);
    }

    /**
     * Conexão que está sempre em um banco existente (para CREATE DATABASE / checagem).
     */
    protected function connectionForCreateDatabase(): string
    {
        return config('raptor.database.landlord_connection_name', 'landlord');
    }

    /**
     * Verifica se o banco de dados existe (usa conexão landlord para não depender do banco novo).
     */
    public function databaseExists(string $database): bool
    {
        try {
            $conn = $this->connectionForCreateDatabase();
            $driver = config("database.connections.{$conn}.driver");
            if ($driver === 'pgsql') {
                $result = DB::connection($conn)
                    ->select('SELECT 1 FROM pg_database WHERE datname = ?', [$database]);
            } else {
                $result = DB::connection($conn)
                    ->select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$database]);
            }

            return count($result) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Cria o banco de dados (usa conexão landlord, que está em um banco que já existe).
     */
    public function createDatabase(string $database): void
    {
        $conn = $this->connectionForCreateDatabase();
        $driver = config("database.connections.{$conn}.driver");
        $charset = config("database.connections.{$conn}.charset", 'utf8');
        if ($driver === 'pgsql') {
            DB::connection($conn)->statement("CREATE DATABASE \"{$database}\" ENCODING '{$charset}'");
        } else {
            DB::connection($conn)->statement("CREATE DATABASE `{$database}` CHARACTER SET {$charset}");
        }
    }

    /**
     * Garante que o banco existe, aponta a default para ele, roda as migrations e insere o tenant.
     * Ordem: 1) verificar/criar banco (via conexão landlord) 2) trocar default para o banco 3) rodar migrations 4) inserir tenant.
     *
     * @param  array<int, string>  $migrationPaths  Pastas de migrations (ex: ['database/migrations/', 'database/migrations/tenant/'])
     * @param  Model|null  $tenant  Se informado, copia os dados do tenant para a nova base; se null, só roda as migrations
     */
    public function ensureDatabaseAndRunMigrations(string $database, array $migrationPaths, ?Model $tenant = null): void
    {
        if (empty($database)) {
            return;
        }

        if (! $this->databaseExists($database)) {
            $this->createDatabase($database);
        }

        $this->setupConnection($database);
        $options = ['--database' => $this->defaultConnection, '--realpath' => false];
        foreach ($migrationPaths as $path) {
            Artisan::call('migrate', array_merge($options, ['--path' => $path]));
        }
        if ($tenant !== null) {
            $this->copyTenantRecordToTenantDatabase($tenant);
        }
    }

    /**
     * Nome da tabela de tenants (landlord e tenant DB).
     */
    protected function tenantsTable(): string
    {
        return config('raptor.tables.tenants', 'tenants');
    }

    /**
     * Insere uma cópia exata do registro na tabela tenants do banco do tenant (mesmo id).
     * Se o tenant não tiver database preenchido, usa o banco default do env.
     */
    public function copyTenantRecordToTenantDatabase(Model $tenant): void
    {
        $database = $tenant->getAttribute('database') ?: $this->getDefaultDatabaseName();
        $this->setupConnection($database);
        $table = $this->tenantsTable();
        $row = $this->tenantModelToRow($tenant);
        if (DB::connection($this->defaultConnection)->table($table)->where('id', $tenant->getKey())->exists()) {
            DB::connection($this->defaultConnection)->table($table)->where('id', $tenant->getKey())->update($row);
        } else {
            DB::connection($this->defaultConnection)->table($table)->insert($row);
        }
    }

    /**
     * Atualiza o registro na tabela tenants do banco do tenant (mesmo id).
     * Se o tenant não tiver database preenchido, usa o banco default do env.
     */
    public function syncTenantRecordToTenantDatabase(Model $tenant): void
    {
        $database = $tenant->getAttribute('database') ?: $this->getDefaultDatabaseName();
        $this->setupConnection($database);
        $table = $this->tenantsTable();
        $row = $this->tenantModelToRow($tenant);
        DB::connection($this->defaultConnection)->table($table)->updateOrInsert(
            ['id' => $tenant->getKey()],
            $row
        );
    }

    /**
     * Remove o registro da tabela tenants do banco do tenant (apenas se tiver database dedicado).
     */
    public function deleteTenantRecordFromTenantDatabase(Model $tenant): void
    {
        $database = $tenant->getAttribute('database');
        if (empty($database)) {
            return;
        }
        $this->setupConnection($database);
        $table = $this->tenantsTable();
        DB::connection($this->defaultConnection)->table($table)->where('id', $tenant->getKey())->delete();
    }

    /**
     * Remove o banco de dados do tenant (usa conexão landlord para não estar conectado ao banco que será dropado).
     */
    public function dropDatabase(string $database): void
    {
        if (empty($database)) {
            return;
        }
        $conn = $this->connectionForCreateDatabase();
        $driver = config("database.connections.{$conn}.driver");
        if ($driver === 'pgsql') {
            DB::connection($conn)->statement("DROP DATABASE IF EXISTS \"{$database}\"");
        } else {
            DB::connection($conn)->statement("DROP DATABASE IF EXISTS `{$database}`");
        }
    }

    /**
     * Cria configuração inicial do tenant quando o banco está vazio (role, permissões, usuário).
     * Sempre envia email ao endereço do tenant: novas credenciais (banco vazio) ou aviso de atualização.
     */
    public function createTenantConfiguration(Model $tenant): void
    {
        $database = $tenant->getAttribute('database');
        if (empty($database)) {
            return;
        }
        $this->setupConnection($database);
        $databaseWasEmpty = $this->tenantDatabaseIsEmpty();
        $class = config('raptor.tenant_configuration.class', DefaultTenantConfiguration::class);
        if (! $class || ! class_exists($class)) {
            return;
        }
        app($class)->run($tenant, $databaseWasEmpty);
    }

    /**
     * Verifica se o banco da conexão tenant está vazio (sem users, roles ou permissions).
     */
    protected function tenantDatabaseIsEmpty(): bool
    {
        $userModelClass = config('raptor.shinobi.models.user');
        $usersTable = (new $userModelClass)->getTable();
        $rolesTable = config('raptor.shinobi.tables.roles');
        $permissionsTable = config('raptor.shinobi.tables.permissions');

        $hasUsers = DB::connection($this->defaultConnection)->table($usersTable)->exists();
        $hasRoles = DB::connection($this->defaultConnection)->table($rolesTable)->exists();
        $hasPermissions = DB::connection($this->defaultConnection)->table($permissionsTable)->exists();

        return ! $hasUsers && ! $hasRoles && ! $hasPermissions;
    }

    /**
     * Converte o model tenant em array para insert/update (cópia exata, mesmo id).
     *
     * @return array<string, mixed>
     */
    protected function tenantModelToRow(Model $tenant): array
    {
        $keyName = $tenant->getKeyName();
        $attributes = $tenant->getAttributes();
        $attributes[$keyName] = $tenant->getKey();
        $out = [];
        foreach ($attributes as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $out[$key] = $value->format($tenant->getDateFormat());
            } elseif (is_array($value) || (is_object($value) && ! $value instanceof \DateTimeInterface)) {
                $out[$key] = json_encode($value);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
