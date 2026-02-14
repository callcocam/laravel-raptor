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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DefaultTenantConfiguration implements TenantConfigurationContract
{
    public function run(Model $tenant, bool $databaseWasEmpty): void
    {
        $email = $tenant->getAttribute('email');
        $user = null;
        $plainPassword = null;

        if ($databaseWasEmpty) {
            $roleModelClass = config('raptor.shinobi.models.role');
            $landlordConnection = config('raptor.database.landlord_connection_name', 'landlord');
            $tenantConnection = config('raptor.database.tenant_connection_name', 'default');

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

            $roleModel = $roleModelClass::on($tenantConnection)->firstOrCreate(
                ['slug' => $roleSlug],
                ['name' => $roleName, 'special' => $special ?? true]
            );

            $tenantDirectories = array_merge(
                config('raptor.route_injector.contexts.tenant', []),
                config('raptor.route_injector.package_directories.tenant', [])
            );
            PermissionGenerator::generate($tenantDirectories)
                ->forConnection($tenantConnection)
                ->save(false);

            if (! empty($email)) {
                $userModelClass = config('raptor.shinobi.models.user');
                $name = $tenant->getAttribute('name') ?: $email ?: 'Administrador';
                $plainPassword = Str::random(16);
                $user = $userModelClass::on($tenantConnection)->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($plainPassword),
                    'tenant_id' => $tenant->getKey(),
                ]);
                $user->roles()->attach($roleModel->getKey());
            }
        }

        if (empty($email)) {
            return;
        }

        $mailableClass = config('raptor.tenant_configuration.mail', \Callcocam\LaravelRaptor\Mail\TenantConfiguredMail::class);
        if ($mailableClass && class_exists($mailableClass)) {
            Mail::to($email)->send(new $mailableClass($tenant, $user, $plainPassword));
        }
    }
}
