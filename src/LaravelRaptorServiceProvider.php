<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor;

use Callcocam\LaravelRaptor\Commands\LaravelRaptorCommand;
use Callcocam\LaravelRaptor\Commands\SyncCommand;
use Callcocam\LaravelRaptor\Http\Middleware\LandlordMiddleware;
use Callcocam\LaravelRaptor\Http\Middleware\TenantMiddleware;
use Callcocam\LaravelRaptor\Http\Middleware\TenantCustomDomainMiddleware;
use Illuminate\Routing\Router;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelRaptorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-raptor')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoutes(['web'])
            ->hasMigrations([
                // Tabelas principais (ordem de dependência)
                'create_tenants_table',
                'create_users_table',
                'create_roles_table',
                'create_permissions_table',

                // Tabelas pivot (relacionamentos muitos-para-muitos)
                'create_role_user_table',
                'create_permission_role_table',
                'create_permission_user_table',

                // Outras tabelas
                'create_addresses_table',

                // Modificações de tabelas
                'add_two_factor_columns_to_users_table',

                // Tabelas de sistema
                'create_personal_access_tokens_table',
                'create_cache_table',
                'create_jobs_table',
            ])
            ->hasCommands([
                LaravelRaptorCommand::class,
                SyncCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                // Customize the install command created by the package tools
                $command->startWith(function (InstallCommand $command) {
                    $command->call('laravel-raptor:sync', [
                        '--force' => true,
                    ]);
                });
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->publishAssets();
                // Optional: You can customize the install command here
            });
    }

    /**
     * Bootstrap any package services.
     */
    public function packageBooted(): void
    {
        // Registra os middlewares
        $this->registerMiddleware();
    }

    /**
     * Register the package middlewares.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Registra os middlewares com alias
        $router->aliasMiddleware('landlord', LandlordMiddleware::class);
        $router->aliasMiddleware('tenant', TenantMiddleware::class);
        $router->aliasMiddleware('tenant.custom.domain', TenantCustomDomainMiddleware::class);
    }
}

