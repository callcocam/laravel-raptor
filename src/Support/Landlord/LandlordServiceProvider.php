<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Landlord;

use Callcocam\LaravelRaptor\Contracts\TenantResolverInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider responsável pelo gerenciamento de multi-tenancy (landlord)
 * Registra o TenantManager e middleware de resolução de tenants
 * Otimizado para SPA com respostas JSON
 */
class LandlordServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap dos eventos da aplicação
     */
    public function boot(): void
    {
        // Detecta e configura tenant/client durante o boot usando TenantResolver
        $request = request();

        if ($request && $request->isTenant()) {
            $resolver = app(TenantResolverInterface::class);
            $resolver->resolve($request);
        }
    }

    /**
     * Registra o service provider no container
     * Configura as dependências necessárias para o sistema de landlord
     */
    public function register(): void
    {
        // Registra o TenantManager como singleton
        $this->app->singleton(TenantManager::class);

        // Singleton 'tenant' retorna tenant já resolvido
        // O TenantResolverInterface é registrado no LaravelRaptorServiceProvider
        $this->app->singleton('tenant', function ($app) {
            $resolver = $app->make(TenantResolverInterface::class);

            return $resolver->getTenant();
        });

        // Registra helper para tenant atual
        $this->app->bind('current.tenant', function () {
            return app('tenant');
        });
    }

    /**
     * Retorna a classe do modelo de tenant configurada
     */
    public function getModel(): string
    {
        return config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
    }

    /**
     * Retorna o tenant ativo na sessão/contexto
     */
    public function getCurrentTenant()
    {
        return app('tenant');
    }

    /**
     * Define o tenant ativo
     */
    public function setCurrentTenant($tenant): void
    {
        app()->instance('tenant', $tenant);
    }
}
