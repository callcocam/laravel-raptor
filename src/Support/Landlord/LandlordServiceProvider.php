<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Landlord;

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
        // Bootstrapping logic if needed
    }

    /**
     * Registra o service provider no container
     * Configura as dependências necessárias para o sistema de landlord
     */
    public function register(): void
    {
        // Registra o singleton para gerenciamento de tenant ativo
        $this->app->singleton('tenant', function () {
            return null; // Será preenchido pelo middleware quando necessário
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
        return config('laravel-raptor.tenant_model', \Callcocam\LaravelRaptor\Models\Tenant::class);
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
