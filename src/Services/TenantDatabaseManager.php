<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Support\ResolvedTenantConfig;
use Illuminate\Database\ConnectionInterface;
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
     * Retorna o nome do banco da conexão landlord (banco principal/canônico).
     */
    public function getLandlordDatabaseName(): string
    {
        $landlordConnection = config('raptor.database.landlord_connection_name', 'landlord');

        return (string) config("database.connections.{$landlordConnection}.database");
    }

    /**
     * Retorna se o banco informado é dedicado (diferente do banco principal).
     */
    public function isDedicatedTenantDatabase(?string $database): bool
    {
        if (empty($database)) {
            return false;
        }

        return $database !== $this->getLandlordDatabaseName();
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
     * Aplica a configuração resolvida do tenant:
     * - Landlord: assume apenas o banco do tenant (tenant->database) ou fica no default do env.
     * - Default: inicialmente igual ao landlord; se houver Client/Store no domainable com banco, a default assume o banco do Client ou Store.
     */
    public function applyConfig(ResolvedTenantConfig $config): void
    {
        $landlordConnection = config('raptor.database.landlord_connection_name', 'landlord');

        // 1. Landlord = banco do tenant (quando preenchido)
        $tenantDatabase = $config->landlordDatabase();
        if ($tenantDatabase !== null) {
            $this->switchConnectionTo($landlordConnection, $tenantDatabase);
        }

        // 2. Default = mesmo que landlord; se houver Client/Store (domainable) com banco, default = banco do Client/Store
        if ($config->hasDedicatedDatabase()) {
            $this->switchConnectionTo($this->defaultConnection, (string) $config->database);
        } else {
            $landlordDb = config("database.connections.{$landlordConnection}.database");
            if (is_string($landlordDb) && $landlordDb !== '') {
                $this->switchConnectionTo($this->defaultConnection, $landlordDb);
            }
        }
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
     * Garante que o banco existe, aponta a default para ele, roda as migrations e sincroniza o tenant.
     * Ordem: 1) verificar/criar banco (via conexão landlord) 2) trocar default para o banco 3) rodar migrations 4) sincronizar tenant.
     *
     * @param  array<int, string>  $migrationPaths  Pastas de migrations (ex: ['database/migrations/', 'database/migrations/tenant/'])
     * @param  Model|null  $tenant  Se informado, sincroniza os dados do tenant para a nova base; se null, só roda as migrations
     * @param  bool  $enforceExactId  Se true, garante cópia com mesmo id canônico do landlord
     */
    public function ensureDatabaseAndRunMigrations(
        string $database,
        array $migrationPaths,
        ?Model $tenant = null,
        bool $enforceExactId = false
    ): void {
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
            $this->copyTenantRecordToTenantDatabase($tenant, $database, $enforceExactId);
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
     * Insere/sincroniza o registro na tabela tenants do banco do tenant.
     * No modo estrito, força alinhamento do id canônico em caso de conflito por slug.
     */
    public function copyTenantRecordToTenantDatabase(
        Model $tenant,
        ?string $database = null,
        bool $enforceExactId = false
    ): void {
        $this->syncTenantRecordToTenantDatabase($tenant, $database, $enforceExactId);
    }

    /**
     * Sincroniza os dados do tenant no banco do tenant.
     *
     * - Se existir por id: atualiza campos não-PK.
     * - Se existir por slug com id diferente:
     *   - modo estrito: tenta realinhar o id para o canônico do landlord.
     *   - modo normal: atualiza campos não-PK sem trocar id.
     * - Se não existir: insere registro completo.
     *
     * @param  string|null  $database  Banco alvo para sincronização (quando null, usa tenant.database)
     * @param  bool  $enforceExactId  Se true, exige id idêntico ao landlord (lança exceção em falha)
     */
    public function syncTenantRecordToTenantDatabase(
        Model $tenant,
        ?string $database = null,
        bool $enforceExactId = false
    ): void {
        $targetDatabase = $this->resolveTargetDatabase($tenant, $database);
        if (empty($targetDatabase)) {
            return;
        }

        $this->setupConnection($targetDatabase);

        $table = $this->tenantsTable();
        $conn = DB::connection($this->defaultConnection);
        $row = $this->tenantModelToRow($tenant);
        $keyName = $tenant->getKeyName();
        $tenantId = (string) $tenant->getKey();
        $slug = (string) ($tenant->getAttribute('slug') ?? '');
        $updateRow = collect($row)->except($keyName)->toArray();

        if ($conn->table($table)->where($keyName, $tenantId)->exists()) {
            $conn->table($table)->where($keyName, $tenantId)->update($updateRow);

            return;
        }

        if ($slug !== '' && $conn->table($table)->where('slug', $slug)->exists()) {
            $conflictingId = (string) $conn->table($table)
                ->where('slug', $slug)
                ->value($keyName);

            if ($conflictingId !== '' && $conflictingId !== $tenantId && $enforceExactId) {
                $this->realignTenantIdBySlug(
                    $conn,
                    $table,
                    $keyName,
                    $slug,
                    $tenantId,
                    $updateRow,
                    $targetDatabase
                );

                return;
            }

            $conn->table($table)->where('slug', $slug)->update($updateRow);

            return;
        }

        $conn->table($table)->insert($row);
    }

    /**
     * Retorna o diagnóstico de identidade de um tenant em um banco específico.
     *
     * @return array{database:string, canonical_id:string, slug:string, exists_by_id:bool, exists_by_slug:bool, slug_conflict_id:?string}
     */
    public function inspectTenantIdentity(Model $tenant, ?string $database = null): array
    {
        $targetDatabase = $this->resolveTargetDatabase($tenant, $database);
        if (empty($targetDatabase)) {
            return [
                'database' => '',
                'canonical_id' => (string) $tenant->getKey(),
                'slug' => (string) ($tenant->getAttribute('slug') ?? ''),
                'exists_by_id' => false,
                'exists_by_slug' => false,
                'slug_conflict_id' => null,
            ];
        }

        $this->setupConnection($targetDatabase);

        $table = $this->tenantsTable();
        $conn = DB::connection($this->defaultConnection);
        $keyName = $tenant->getKeyName();
        $tenantId = (string) $tenant->getKey();
        $slug = (string) ($tenant->getAttribute('slug') ?? '');

        $existsById = $conn->table($table)->where($keyName, $tenantId)->exists();
        $existsBySlug = $slug !== '' && $conn->table($table)->where('slug', $slug)->exists();

        $slugConflictId = null;
        if (! $existsById && $existsBySlug) {
            $foundId = $conn->table($table)->where('slug', $slug)->value($keyName);
            if ($foundId !== null && (string) $foundId !== $tenantId) {
                $slugConflictId = (string) $foundId;
            }
        }

        return [
            'database' => $targetDatabase,
            'canonical_id' => $tenantId,
            'slug' => $slug,
            'exists_by_id' => $existsById,
            'exists_by_slug' => $existsBySlug,
            'slug_conflict_id' => $slugConflictId,
        ];
    }

    /**
     * Remove o registro da tabela tenants de um banco específico (por id/slug).
     */
    public function deleteTenantRecordFromDatabase(Model $tenant, string $database, ?string $slug = null): void
    {
        if (empty($database)) {
            return;
        }

        $this->setupConnection($database);
        $table = $this->tenantsTable();
        $query = DB::connection($this->defaultConnection)
            ->table($table)
            ->where($tenant->getKeyName(), $tenant->getKey());

        $slugToMatch = $slug ?? $tenant->getAttribute('slug');
        if (! empty($slugToMatch)) {
            $query->orWhere('slug', (string) $slugToMatch);
        }

        $query->delete();
    }

    /**
     * Remove o registro da tabela tenants do banco do tenant (apenas se tiver database dedicado).
     */
    public function deleteTenantRecordFromTenantDatabase(Model $tenant): void
    {
        $database = $tenant->getAttribute('database');
        if (! $this->isDedicatedTenantDatabase($database)) {
            return;
        }

        $this->deleteTenantRecordFromDatabase($tenant, (string) $database);
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
     * Cria configuração inicial do tenant (role super-admin, permissões, usuário).
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
     * Sincroniza permissões do contexto tenant no banco do tenant.
     *
     * Não remove permissões extras; apenas cria faltantes e normaliza existentes.
     *
     * @return array{expected:int, created:int, updated:int}
     */
    public function syncTenantPermissions(Model $tenant, ?string $database = null): array
    {
        $targetDatabase = $this->resolveTargetDatabase($tenant, $database);
        if (empty($targetDatabase)) {
            return [
                'expected' => 0,
                'created' => 0,
                'updated' => 0,
            ];
        }

        $this->setupConnection($targetDatabase);

        return app(PermissionCatalogService::class)->syncPermissionsForConnection(
            $this->defaultConnection,
            'tenant',
            false
        );
    }

    /**
     * Verifica se o banco da conexão default (tenant) está vazio (sem users, roles ou permissions).
     * Em erro (ex.: tabelas ainda não existem), considera vazio para rodar a configuração.
     */
    protected function tenantDatabaseIsEmpty(): bool
    {
        try {
            $userModelClass = config('raptor.shinobi.models.user');
            $usersTable = (new $userModelClass)->getTable();
            $rolesTable = config('raptor.shinobi.tables.roles');
            $permissionsTable = config('raptor.shinobi.tables.permissions');

            $conn = $this->defaultConnection;
            $hasUsers = DB::connection($conn)->table($usersTable)->exists();
            $hasRoles = DB::connection($conn)->table($rolesTable)->exists();
            $hasPermissions = DB::connection($conn)->table($permissionsTable)->exists();

            return ! $hasUsers && ! $hasRoles && ! $hasPermissions;
        } catch (\Throwable $e) {
            return true;
        }
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

    /**
     * Resolve o banco alvo para operações de sincronização.
     */
    protected function resolveTargetDatabase(Model $tenant, ?string $database = null): string
    {
        if (! empty($database)) {
            return (string) $database;
        }

        $tenantDatabase = $tenant->getAttribute('database');
        if (! empty($tenantDatabase)) {
            return (string) $tenantDatabase;
        }

        return $this->getDefaultDatabaseName();
    }

    /**
     * Realinha o id do registro existente no banco do tenant para o id canônico do landlord.
     *
     * @param  array<string, mixed>  $updateRow
     */
    protected function realignTenantIdBySlug(
        ConnectionInterface $conn,
        string $table,
        string $keyName,
        string $slug,
        string $targetId,
        array $updateRow,
        string $database
    ): void {
        $existingId = (string) $conn->table($table)
            ->where('slug', $slug)
            ->value($keyName);

        if ($existingId === '' || $existingId === $targetId) {
            $conn->table($table)->where('slug', $slug)->update($updateRow);

            return;
        }

        if ($conn->table($table)->where($keyName, $targetId)->exists()) {
            throw new \RuntimeException(sprintf(
                'Conflito de identidade do tenant no banco "%s": já existe registro com id "%s" e slug "%s" associado a id "%s".',
                $database,
                $targetId,
                $slug,
                $existingId
            ));
        }

        $conn->beginTransaction();
        try {
            // Discover FK child tables referencing tenants.id so they can be
            // re-parented within the same transaction (avoids FK violations).
            $children = $this->getFkChildRows($conn, $table, $keyName, $existingId);

            // Delete child rows that reference the old id (saved above).
            foreach ($children as $childTable => $data) {
                $conn->table($childTable)->where($data['column'], $existingId)->delete();
            }

            $affected = $conn->table($table)
                ->where($keyName, $existingId)
                ->where('slug', $slug)
                ->update([$keyName => $targetId]);

            if ($affected !== 1) {
                throw new \RuntimeException(sprintf(
                    'Falha ao realinhar id do tenant no banco "%s" (slug "%s").',
                    $database,
                    $slug
                ));
            }

            // Re-insert child rows with the new parent id.
            foreach ($children as $childTable => $data) {
                foreach ($data['rows'] as $row) {
                    $rowArr = (array) $row;
                    $rowArr[$data['column']] = $targetId;
                    $conn->table($childTable)->insert($rowArr);
                }
            }

            $conn->table($table)->where($keyName, $targetId)->update($updateRow);
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();

            throw new \RuntimeException(sprintf(
                'Falha ao forçar identidade canônica do tenant no banco "%s" para id "%s" (slug "%s"): %s',
                $database,
                $targetId,
                $slug,
                $e->getMessage()
            ), 0, $e);
        }
    }

    /**
     * Descobre tabelas filhas que têm FK apontando para $parentTable.$keyName
     * e retorna os registros que referenciam $parentId.
     *
     * Suporta PostgreSQL e MySQL. Para outros drivers retorna array vazio.
     *
     * @return array<string, array{column: string, rows: array<int, object>}>
     */
    protected function getFkChildRows(
        ConnectionInterface $conn,
        string $parentTable,
        string $keyName,
        string $parentId
    ): array {
        $driver = $conn->getDriverName();

        $childMeta = match ($driver) {
            'pgsql' => $conn->select("
                SELECT kcu.table_name  AS child_table,
                       kcu.column_name AS fk_column
                FROM   information_schema.table_constraints     AS tc
                JOIN   information_schema.key_column_usage      AS kcu
                       ON  tc.constraint_name  = kcu.constraint_name
                       AND tc.table_schema     = kcu.table_schema
                JOIN   information_schema.constraint_column_usage AS ccu
                       ON  ccu.constraint_name = tc.constraint_name
                       AND ccu.table_schema    = tc.table_schema
                WHERE  tc.constraint_type = 'FOREIGN KEY'
                  AND  ccu.table_name     = ?
                  AND  ccu.column_name    = ?
                  AND  tc.table_schema    = current_schema()
            ", [$parentTable, $keyName]),

            'mysql' => $conn->select("
                SELECT TABLE_NAME  AS child_table,
                       COLUMN_NAME AS fk_column
                FROM   information_schema.KEY_COLUMN_USAGE
                WHERE  REFERENCED_TABLE_NAME   = ?
                  AND  REFERENCED_COLUMN_NAME  = ?
                  AND  TABLE_SCHEMA            = DATABASE()
            ", [$parentTable, $keyName]),

            default => [],
        };

        $result = [];
        foreach ($childMeta as $meta) {
            $childTable = (string) ($meta->child_table ?? $meta->TABLE_NAME ?? '');
            $fkColumn = (string) ($meta->fk_column ?? $meta->COLUMN_NAME ?? '');

            if ($childTable === '' || $fkColumn === '' || $childTable === $parentTable) {
                continue;
            }

            $rows = $conn->table($childTable)->where($fkColumn, $parentId)->get()->toArray();
            if (! empty($rows)) {
                $result[$childTable] = ['column' => $fkColumn, 'rows' => $rows];
            }
        }

        return $result;
    }
}
