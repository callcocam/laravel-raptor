<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Contracts\TenantConfigurationContract;
use Callcocam\LaravelRaptor\Enums\RoleStatus;
use Callcocam\LaravelRaptor\Enums\UserStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DefaultTenantConfiguration implements TenantConfigurationContract
{
    public function run(Model $tenant, bool $databaseWasEmpty): void
    {
        $email = $tenant->getAttribute('email');
        $user = null;
        $plainPassword = null;

        $tenantConnection = config('database.default');

        // 1. Role: cria super-admin (special) no banco do tenant via DB (model pode forçar landlord)
        $superAdminRoleId = $this->ensureSuperAdminRoleExists($tenant, $tenantConnection);
        if ($superAdminRoleId === null) {
            return;
        }

        // 2. Permissions: upsert (não apaga as existentes)
        $this->ensurePermissionsExist($tenantConnection);

        // 3. User: cria só se não existir para este email no tenant
        if (! empty($email)) {
            [$user, $plainPassword] = $this->ensureUserExists(
                $tenant,
                $email,
                $superAdminRoleId,
                $tenantConnection
            );
        }

        if (empty($email)) {
            return;
        }

        $mailableClass = config('raptor.tenant_configuration.mail', \Callcocam\LaravelRaptor\Mail\TenantConfiguredMail::class);
        if ($mailableClass && class_exists($mailableClass) && $user !== null) {
            Mail::to($email)->send(new $mailableClass($tenant, $user, $plainPassword));
        }
    }

    /**
     * Garante que a role super-admin (special) existe no banco do tenant. Cria via DB para não depender do model (que pode forçar conexão landlord). Retorna o id da role ou null.
     */
    protected function ensureSuperAdminRoleExists(Model $tenant, string $tenantConnection): ?string
    {
        $rolesTable = config('raptor.shinobi.tables.roles', 'roles');

        try {
            $row = DB::connection($tenantConnection)
                ->table($rolesTable)
                ->where('slug', 'super-admin')
                ->whereNull('deleted_at')
                ->first();

            if ($row !== null) {
                return $row->id;
            }

            $id = (string) Str::ulid();
            $now = now();

            DB::connection($tenantConnection)->table($rolesTable)->insert([
                'id' => $id,
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Acesso total ao sistema',
                'status' => RoleStatus::Published->value,
                'special' => true,
                'tenant_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $id;
        } catch (\Throwable $e) {
            Log::warning('DefaultTenantConfiguration: falha ao criar role super-admin no tenant.', [
                'tenant_id' => $tenant->getKey(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Garante que as permissões existem no tenant (upsert, não apaga existentes).
     */
    protected function ensurePermissionsExist(string $tenantConnection): void
    {
        try {
            $tenantDirectories = config('raptor.route_injector.contexts.tenant', []);
            PermissionGenerator::generate($tenantDirectories)
                ->forConnection($tenantConnection)
                ->save(false);
        } catch (\Throwable $e) {
            Log::warning('DefaultTenantConfiguration: falha ao garantir permissões.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Garante que o usuário existe no tenant para o email. Cria via DB (model pode forçar landlord).
     * Retorna [user, plainPassword] ou [null, null]. plainPassword só preenchido quando criou o user.
     * user é um objeto com getAttribute() para compatibilidade com o Mailable/view.
     *
     * @param  string  $superAdminRoleId  ID da role super-admin (criada via DB no tenant).
     * @return array{0: object|null, 1: string|null}
     */
    protected function ensureUserExists(Model $tenant, string $email, string $superAdminRoleId, string $tenantConnection): array
    {
        $usersTable = config('raptor.tables.users', 'users');
        $roleUserTable = config('raptor.tables.role_user', 'role_user');

        try {
            $existing = DB::connection($tenantConnection)
                ->table($usersTable)
                ->where('email', $email)
                ->whereNull('deleted_at')
                ->first();

            if ($existing !== null) {
                return [$this->userLike($existing), null];
            }

            $userId = (string) Str::ulid();
            $name = $tenant->getAttribute('name') ?: $email ?: 'Administrador';
            $plainPassword = Str::random(16);
            $now = now();

            DB::connection($tenantConnection)->table($usersTable)->insert([
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'slug' => Str::slug($name),
                'status' => UserStatus::Published->value,
                'password' => Hash::make($plainPassword),
                'tenant_id' => $tenant->getKey(),
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::connection($tenantConnection)->table($roleUserTable)->insert([
                'role_id' => $superAdminRoleId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return [$this->userLike((object) ['id' => $userId, 'name' => $name, 'email' => $email]), $plainPassword];
        } catch (\Throwable $e) {
            Log::warning('DefaultTenantConfiguration: falha ao garantir usuário.', [
                'email' => $email,
                'tenant_id' => $tenant->getKey(),
                'error' => $e->getMessage(),
            ]);

            return [null, null];
        }
    }

    /**
     * Objeto compatível com getAttribute() para o Mailable/view (evita depender do Model User).
     *
     * @param  object  $row  stdClass ou objeto com id, name, email
     */
    protected function userLike(object $row): object
    {
        return new class($row)
        {
            public function __construct(private readonly object $row) {}

            public function getAttribute(string $key): mixed
            {
                return $this->row->{$key} ?? null;
            }
        };
    }
}
