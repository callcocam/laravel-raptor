<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Contracts\TenantConfigurationContract;
use Illuminate\Database\Eloquent\Model;
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

        $landlordConnection = config('raptor.database.landlord_connection_name', 'landlord');
        $tenantConnection = config('raptor.database.tenant_connection_name', 'default');
        $roleModelClass = config('raptor.shinobi.models.role');

        // 1. Role: cria só se não existir (firstOrCreate)
        $roleModel = $this->ensureRoleExists($tenant, $roleModelClass, $landlordConnection, $tenantConnection);
        if (! $roleModel) {
            return;
        }

        // 2. Permissions: upsert (não apaga as existentes)
        $this->ensurePermissionsExist($tenantConnection);

        // 3. User: cria só se não existir para este email no tenant
        if (! empty($email)) {
            [$user, $plainPassword] = $this->ensureUserExists(
                $tenant,
                $email,
                $roleModel,
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
     * Garante que a role existe no tenant (copia do landlord se necessário). Retorna a role ou null em erro.
     */
    protected function ensureRoleExists(Model $tenant, string $roleModelClass, string $landlordConnection, string $tenantConnection): ?Model
    {
        try {
            $sourceRole = $roleModelClass::on($landlordConnection)
                ->where(function ($q) {
                    $q->orWhereNotNull('special');
                })
                ->first();

            if ($sourceRole) {
                $roleName = $sourceRole->getAttribute('name');
                $roleSlug = $sourceRole->getAttribute('slug') ?: Str::slug($roleName);
                $special = $sourceRole->getAttribute('special');
            } else {
                $roleName = 'Super Administrador';
                $roleSlug = 'super-administrador';
                $special = true;
            }

            return $roleModelClass::on($tenantConnection)->firstOrCreate(
                ['slug' => $roleSlug],
                ['name' => $roleName, 'special' => $special ?? true]
            );
        } catch (\Throwable $e) {
            Log::warning('DefaultTenantConfiguration: falha ao garantir role.', [
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
            $tenantDirectories = array_merge(
                config('raptor.route_injector.contexts.tenant', []),
                config('raptor.route_injector.package_directories.tenant', [])
            );
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
     * Garante que o usuário existe no tenant para o email. Cria só se não existir.
     * Retorna [user, plainPassword] ou [null, null]. plainPassword só preenchido quando criou o user.
     *
     * @return array{0: Model|null, 1: string|null}
     */
    protected function ensureUserExists(Model $tenant, string $email, Model $roleModel, string $tenantConnection): array
    {
        $userModelClass = config('raptor.shinobi.models.user');

        try {
            $existing = $userModelClass::on($tenantConnection)
                ->where('email', $email)
                ->first();

            if ($existing) {
                return [$existing, null];
            }

            $name = $tenant->getAttribute('name') ?: $email ?: 'Administrador';
            $plainPassword = Str::random(16);
            $user = $userModelClass::on($tenantConnection)->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($plainPassword),
                'tenant_id' => $tenant->getKey(),
            ]);

            if (! $user->roles()->where($roleModel->getKeyName(), $roleModel->getKey())->exists()) {
                $user->roles()->attach($roleModel->getKey());
            }

            return [$user, $plainPassword];
        } catch (\Throwable $e) {
            Log::warning('DefaultTenantConfiguration: falha ao garantir usuário.', [
                'email' => $email,
                'tenant_id' => $tenant->getKey(),
                'error' => $e->getMessage(),
            ]);

            return [null, null];
        }
    }
}
