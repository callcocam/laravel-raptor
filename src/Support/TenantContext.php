<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support;

use Callcocam\LaravelRaptor\Enums\TenantStatus;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;

/**
 * Helper para gerenciar contexto do tenant em qualquer lugar
 *
 * Útil para Jobs, Commands, Listeners, ou qualquer código que
 * precisa restaurar o contexto do tenant fora de uma requisição HTTP.
 *
 * @example
 * ```php
 * use Callcocam\LaravelRaptor\Support\TenantContext;
 *
 * // Configura tenant por ID
 * TenantContext::set($tenantId);
 *
 * // Executa código no contexto de um tenant
 * TenantContext::run($tenantId, function ($tenant) {
 *     // Seu código aqui
 * });
 *
 * // Executa para todos os tenants
 * TenantContext::forAll(function ($tenant) {
 *     // Seu código aqui - contexto já configurado
 * });
 *
 * // Obtém dados do contexto atual
 * $tenant = TenantContext::current();
 * $tenantId = TenantContext::id();
 * ```
 */
class TenantContext
{
    /**
     * Configura o contexto de um tenant pelo ID
     */
    public static function set(string $tenantId, ?string $domainableType = null, ?string $domainableId = null): bool
    {
        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $tenant = $tenantModel::find($tenantId);

        if (! $tenant) {
            return false;
        }

        static::setFromModel($tenant, $domainableType, $domainableId);

        return true;
    }

    /**
     * Configura o contexto a partir de uma instância do tenant
     */
    public static function setFromModel($tenant, ?string $domainableType = null, ?string $domainableId = null): void
    {
        // Registra no container
        app()->instance('tenant.context', true);
        app()->instance('current.tenant', $tenant);
        app()->instance('tenant', $tenant);

        // Registra na config
        config(['app.context' => 'tenant']);
        config(['app.current_tenant_id' => $tenant->id]);

        // Adiciona ao Landlord para scopes automáticos
        Landlord::addTenant($tenant);

        // Configura domainable se fornecido
        if ($domainableType && $domainableId) {
            static::setDomainable($domainableType, $domainableId);
        }

        // Configura banco de dados se necessário
        static::configureDatabaseConnection($tenant);
    }

    /**
     * Configura o domainable (Client, Store, etc)
     */
    public static function setDomainable(string $type, string $id): bool
    {
        if (! class_exists($type)) {
            return false;
        }

        $domainable = $type::find($id);

        if (! $domainable) {
            return false;
        }

        app()->instance('current.domainable', $domainable);
        app()->instance('current.domainable_type', $type);
        app()->instance('current.domainable_id', $id);

        config(['app.current_domainable_type' => $type]);
        config(['app.current_domainable_id' => $id]);

        // Shortcuts
        if ($type === 'App\\Models\\Client') {
            config(['app.current_client_id' => $id]);
            app()->instance('current.client', $domainable);
        }

        if ($type === 'App\\Models\\Store') {
            config(['app.current_store_id' => $id]);
            app()->instance('current.store', $domainable);
        }

        return true;
    }

    /**
     * Executa uma callback no contexto de um tenant específico
     * Restaura o contexto anterior após execução
     */
    public static function run(string $tenantId, callable $callback, ?string $domainableType = null, ?string $domainableId = null)
    {
        // Salva contexto atual
        $previousTenantId = config('app.current_tenant_id');
        $previousDomainableType = config('app.current_domainable_type');
        $previousDomainableId = config('app.current_domainable_id');

        try {
            // Configura novo contexto
            if (! static::set($tenantId, $domainableType, $domainableId)) {
                throw new \RuntimeException("Tenant não encontrado: {$tenantId}");
            }

            $tenant = static::current();

            return $callback($tenant);
        } finally {
            // Restaura contexto anterior
            static::clear();

            if ($previousTenantId) {
                static::set($previousTenantId, $previousDomainableType, $previousDomainableId);
            }
        }
    }

    /**
     * Executa uma callback para todos os tenants ativos
     */
    public static function forAll(callable $callback): array
    {
        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $tenants = $tenantModel::where('status', TenantStatus::Published->value)->get();

        $results = [];

        foreach ($tenants as $tenant) {
            try {
                static::setFromModel($tenant);
                $results[$tenant->id] = $callback($tenant);
            } catch (\Exception $e) {
                $results[$tenant->id] = ['error' => $e->getMessage()];
            } finally {
                static::clear();
            }
        }

        return $results;
    }

    /**
     * Limpa o contexto atual
     */
    public static function clear(): void
    {
        Landlord::disable();

        config([
            'app.context' => null,
            'app.current_tenant_id' => null,
            'app.current_domainable_type' => null,
            'app.current_domainable_id' => null,
            'app.current_client_id' => null,
            'app.current_store_id' => null,
        ]);

        app()->forgetInstance('tenant');
        app()->forgetInstance('current.tenant');
        app()->forgetInstance('tenant.context');
        app()->forgetInstance('current.domainable');
        app()->forgetInstance('current.client');
        app()->forgetInstance('current.store');
    }

    /**
     * Retorna o tenant atual
     */
    public static function current()
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }

    /**
     * Retorna o ID do tenant atual
     */
    public static function id(): ?string
    {
        return config('app.current_tenant_id');
    }

    /**
     * Verifica se há um tenant configurado
     */
    public static function has(): bool
    {
        return static::id() !== null;
    }

    /**
     * Retorna o domainable atual (Client, Store, etc)
     */
    public static function domainable()
    {
        return app()->bound('current.domainable') ? app('current.domainable') : null;
    }

    /**
     * Retorna o client atual (se configurado)
     */
    public static function client()
    {
        return app()->bound('current.client') ? app('current.client') : null;
    }

    /**
     * Retorna a store atual (se configurada)
     */
    public static function store()
    {
        return app()->bound('current.store') ? app('current.store') : null;
    }

    /**
     * Configura a conexão de banco de dados do tenant
     */
    protected static function configureDatabaseConnection($tenant): void
    {
        if (config('raptor.database.strategy') !== 'separate') {
            return;
        }

        if (! class_exists(\Callcocam\LaravelRaptor\Services\TenantConnectionService::class)) {
            return;
        }

        app(\Callcocam\LaravelRaptor\Services\TenantConnectionService::class)
            ->configureTenantDatabase($tenant);
    }

    /**
     * Serializa o contexto atual para uso em Jobs
     *
     * @return array Dados serializáveis
     */
    public static function serialize(): array
    {
        return [
            'tenant_id' => config('app.current_tenant_id'),
            'domainable_type' => config('app.current_domainable_type'),
            'domainable_id' => config('app.current_domainable_id'),
        ];
    }

    /**
     * Restaura o contexto a partir de dados serializados
     */
    public static function unserialize(array $data): bool
    {
        if (empty($data['tenant_id'])) {
            return false;
        }

        return static::set(
            $data['tenant_id'],
            $data['domainable_type'] ?? null,
            $data['domainable_id'] ?? null
        );
    }
}
