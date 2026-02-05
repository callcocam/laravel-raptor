<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor;

use Callcocam\LaravelRaptor\Commands\CheckPermissions;
use Callcocam\LaravelRaptor\Commands\LaravelRaptorCommand;
use Callcocam\LaravelRaptor\Commands\RaptorGenerateCommand;
use Callcocam\LaravelRaptor\Commands\RaptorMakeControllerCommand;
use Callcocam\LaravelRaptor\Commands\RaptorMakeModelCommand;
use Callcocam\LaravelRaptor\Commands\RaptorMakePolicyCommand;
use Callcocam\LaravelRaptor\Commands\SyncCommand;
use Callcocam\LaravelRaptor\Commands\TenantMigrateCommand;
use Callcocam\LaravelRaptor\Commands\TranslationGenerateJsonCommand;
use Callcocam\LaravelRaptor\Commands\TranslationSyncCommand;
use Callcocam\LaravelRaptor\Console\Commands\ThemeSetupCommand;
use Callcocam\LaravelRaptor\Http\Middleware\LandlordMiddleware;
use Callcocam\LaravelRaptor\Http\Middleware\ShareRaptorData;
use Callcocam\LaravelRaptor\Http\Middleware\TenantCustomDomainMiddleware;
use Callcocam\LaravelRaptor\Http\Middleware\TenantMiddleware;
use Callcocam\LaravelRaptor\Models\Auth\User;
use Callcocam\LaravelRaptor\Models\Inspiration;
use Callcocam\LaravelRaptor\Models\Permission;
use Callcocam\LaravelRaptor\Models\Role;
use Callcocam\LaravelRaptor\Models\Tenant;
use Callcocam\LaravelRaptor\Policies\InspirationPolicy;
use Callcocam\LaravelRaptor\Policies\PermissionPolicy;
use Callcocam\LaravelRaptor\Policies\RolePolicy;
use Callcocam\LaravelRaptor\Policies\TenantPolicy;
use Callcocam\LaravelRaptor\Policies\UserPolicy;
use Callcocam\LaravelRaptor\Contracts\TenantResolverInterface;
use Callcocam\LaravelRaptor\Services\DomainDetectionService;
use Callcocam\LaravelRaptor\Services\TenantResolver;
use Callcocam\LaravelRaptor\Services\TenantRouteInjector;
use Callcocam\LaravelRaptor\Support\Landlord\LandlordServiceProvider;
use Callcocam\LaravelRaptor\Support\Shinobi\ShinobiServiceProvider;
use Callcocam\LaravelRaptor\Traits\RequestMacrosTrait;
use Callcocam\LaravelRaptor\Notifications\Channels\TenantAwareDatabaseChannel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelRaptorServiceProvider extends PackageServiceProvider
{
    use RequestMacrosTrait;

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
            // Rotas são carregadas condicionalmente por contexto em registerContextRoutes()
            ->hasMigrations([
                // Tabelas principais (ordem de dependência)
                'create_tenants_table',
                'create_tenant_domains_table',
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
                'create_translation_groups_table',
                'create_translation_overrides_table',
                'create_inspirations_table',
            ])
            ->hasCommands([
                LaravelRaptorCommand::class,
                SyncCommand::class,
                ThemeSetupCommand::class,
                RaptorGenerateCommand::class,
                RaptorMakeModelCommand::class,
                RaptorMakeControllerCommand::class,
                RaptorMakePolicyCommand::class,
                TranslationGenerateJsonCommand::class,
                TranslationSyncCommand::class,
                CheckPermissions::class,
                TenantMigrateCommand::class,
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

    public function packageRegistered()
    {
        $this->app->register(LandlordServiceProvider::class);
        $this->app->register(ShinobiServiceProvider::class);

        // Registra o serviço de detecção de domínio como singleton para performance
        $this->app->singleton(DomainDetectionService::class);

        // Registra o TenantResolver baseado na configuração
        // Permite que a aplicação use uma implementação customizada
        $this->app->singleton(TenantResolverInterface::class, function ($app) {
            $resolverClass = config('raptor.services.tenant_resolver', TenantResolver::class);
            return new $resolverClass();
        });

        // Alias para facilitar o uso
        $this->app->alias(TenantResolverInterface::class, TenantResolver::class);
        $this->app->alias(TenantResolverInterface::class, 'tenant.resolver');

        $this->registerRequestMacros();
    }

    /**
     * Bootstrap any package services.
     */
    public function packageBooted(): void
    {
        // Registra os middlewares
        $this->registerMiddleware();

        // Registra as rotas de API
        $this->registerApiRoutes();

        // Registra rotas separadas por contexto (tenant/landlord)
        $this->registerContextRoutes();

        // Registra os canais de broadcast
        $this->registerBroadcastChannels();

        // Registra o canal de notificação customizado
        $this->registerNotificationChannels();

        // Registra as rotas dinamicas dos tenants (legado - mantido para retrocompatibilidade)
        $this->registerTenantRoutes();

        // Registra as policies
        $this->registerPolicies();
    }

    /**
     * Registra as rotas da API com prefixo correto
     */
    protected function registerApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware(['web', 'auth'])
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });
    }

    /**
     * Registra os canais de broadcast do pacote
     */
    protected function registerBroadcastChannels(): void
    {
        if (file_exists($channelsPath = __DIR__ . '/../routes/channels.php')) {
            require $channelsPath;
        }
    }

    /**
     * Registra o canal de notificação customizado que salva tenant_id e client_id
     */
    protected function registerNotificationChannels(): void
    {
        // Substitui o canal 'database' padrão pelo nosso que inclui tenant_id e client_id
        Notification::extend('database', function ($app) {
            return new TenantAwareDatabaseChannel();
        });
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
        $router->aliasMiddleware('raptor.share', ShareRaptorData::class);

        // Adiciona o middleware ao grupo 'web' para compartilhar dados automaticamente
        $router->pushMiddlewareToGroup('web', ShareRaptorData::class);
    }

    /**
     * Registra as rotas dinamicas dos tenants
     */
    protected function registerTenantRoutes(): void
    {
        // Route::middleware(['web','auth', 'tenant'])
        //     ->name('tenant.')
        //     ->group(function () {
        //         $injector = new TenantRouteInjector();
        //         $injector->registerRoutes();
        //     });
    }

    /**
     * Registra as rotas baseadas no contexto (tenant ou landlord).
     * 
     * Detecta o contexto atual e carrega APENAS o arquivo de rotas correspondente.
     * Isso evita conflitos de rotas com mesma URI em contextos diferentes.
     * 
     * Em ambiente CLI (artisan), carrega AMBOS os contextos para que comandos
     * como route:list e permissions:check funcionem corretamente.
     */
    protected function registerContextRoutes(): void
    {
        // Em ambiente CLI, carrega ambos os contextos
        if (app()->runningInConsole()) {
            $this->registerRoutesForContext('tenant');
            $this->registerRoutesForContext('landlord');
            return;
        }
        
        // Em ambiente web, carrega apenas o contexto detectado
        $context = request()->getContext() ?? 'tenant';
        $this->registerRoutesForContext($context);
    }
    
    /**
     * Registra as rotas para um contexto específico.
     */
    protected function registerRoutesForContext(string $context): void
    {
        $routeFile = sprintf('%s/../routes/%s.php', __DIR__, $context);
        
        if (!file_exists($routeFile)) {
            return;
        }
        
        Route::middleware(['web', 'auth', $context])
            ->name(sprintf('%s.', $context))
            ->group($routeFile);
    }

    /**
     * Registra as policies do pacote
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Inspiration::class, InspirationPolicy::class);
    }
}
