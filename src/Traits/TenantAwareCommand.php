<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Traits;

use Callcocam\LaravelRaptor\Enums\TenantStatus;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;
use Illuminate\Console\Command;

/**
 * Trait para Commands que precisam do contexto do tenant
 * 
 * Adiciona opção --tenant para especificar o tenant
 * e métodos para restaurar o contexto.
 * 
 * @example
 * ```php
 * class ProcessOrdersCommand extends Command
 * {
 *     use \Callcocam\LaravelRaptor\Traits\TenantAwareCommand;
 *     
 *     protected $signature = 'orders:process {--tenant= : ID ou domínio do tenant}';
 *     
 *     public function handle(): int
 *     {
 *         // Configura o tenant pelo argumento --tenant
 *         if (!$this->setupTenantFromOption()) {
 *             return Command::FAILURE;
 *         }
 *         
 *         // Agora pode usar app('tenant'), config('app.current_tenant_id'), etc.
 *         $this->info("Processando para tenant: " . app('tenant')->name);
 *         
 *         return Command::SUCCESS;
 *     }
 * }
 * ```
 * 
 * Para iterar sobre todos os tenants:
 * ```php
 * public function handle(): int
 * {
 *     $this->forEachTenant(function ($tenant) {
 *         $this->info("Processando: {$tenant->name}");
 *         // Seu código aqui - contexto do tenant já configurado
 *     });
 *     
 *     return Command::SUCCESS;
 * }
 * ```
 */
trait TenantAwareCommand
{
    /**
     * Configura o contexto do tenant a partir da opção --tenant
     * 
     * @return bool True se conseguiu configurar, false se falhou
     */
    protected function setupTenantFromOption(): bool
    {
        $tenantIdentifier = $this->option('tenant');

        if (!$tenantIdentifier) {
            $this->error('A opção --tenant é obrigatória');
            return false;
        }

        $tenant = $this->findTenant($tenantIdentifier);

        if (!$tenant) {
            $this->error("Tenant não encontrado: {$tenantIdentifier}");
            return false;
        }

        $this->setupTenantContext($tenant);
        return true;
    }

    /**
     * Configura o contexto do tenant opcional (não falha se não informado)
     * 
     * @return bool True se configurou um tenant, false se não tinha tenant para configurar
     */
    protected function setupTenantFromOptionIfProvided(): bool
    {
        $tenantIdentifier = $this->option('tenant');

        if (!$tenantIdentifier) {
            return false;
        }

        $tenant = $this->findTenant($tenantIdentifier);

        if (!$tenant) {
            $this->warn("Tenant não encontrado: {$tenantIdentifier}");
            return false;
        }

        $this->setupTenantContext($tenant);
        return true;
    }

    /**
     * Executa uma callback para cada tenant ativo
     * 
     * @param callable $callback Função que recebe o tenant
     * @param bool $showProgress Exibe barra de progresso
     */
    protected function forEachTenant(callable $callback, bool $showProgress = true): void
    {
        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        
        $tenants = $tenantModel::where('status', TenantStatus::Published->value)->get();

        if ($tenants->isEmpty()) {
            $this->warn('Nenhum tenant ativo encontrado.');
            return;
        }

        $progress = $showProgress ? $this->output->createProgressBar($tenants->count()) : null;

        foreach ($tenants as $tenant) {
            // Configura contexto do tenant atual
            $this->setupTenantContext($tenant);

            try {
                $callback($tenant);
            } catch (\Exception $e) {
                $this->error("Erro no tenant {$tenant->name}: {$e->getMessage()}");
            } finally {
                // Limpa contexto para o próximo
                $this->clearTenantContext();
            }

            $progress?->advance();
        }

        $progress?->finish();
        $this->newLine();
    }

    /**
     * Busca tenant por ID ou domínio
     */
    protected function findTenant(string $identifier)
    {
        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        $domainColumn = config('raptor.tenant.subdomain_column', 'domain');

        // Tenta buscar por ID (UUID ou incremental)
        $tenant = $tenantModel::find($identifier);

        if ($tenant) {
            return $tenant;
        }

        // Tenta buscar por domínio
        return $tenantModel::where($domainColumn, $identifier)->first();
    }

    /**
     * Configura o contexto de um tenant específico
     */
    protected function setupTenantContext($tenant): void
    {
        // Limpa contexto anterior
        $this->clearTenantContext();

        // Registra no container
        app()->instance('tenant.context', true);
        app()->instance('current.tenant', $tenant);
        app()->instance('tenant', $tenant);

        // Registra na config
        config(['app.context' => 'tenant']);
        config(['app.current_tenant_id' => $tenant->id]);

        // Adiciona ao Landlord para scopes automáticos
        Landlord::addTenant($tenant);

        // Configura banco de dados se necessário
        $this->configureTenantDatabaseForCommand($tenant);
    }

    /**
     * Limpa o contexto do tenant atual
     */
    protected function clearTenantContext(): void
    {
        // Remove do Landlord
        Landlord::disable();
        
        // Limpa config
        config([
            'app.context' => null,
            'app.current_tenant_id' => null,
            'app.current_domainable_type' => null,
            'app.current_domainable_id' => null,
            'app.current_client_id' => null,
            'app.current_store_id' => null,
        ]);

        // Remove instâncias do container (se possível)
        app()->forgetInstance('tenant');
        app()->forgetInstance('current.tenant');
        app()->forgetInstance('tenant.context');
        app()->forgetInstance('current.domainable');
        app()->forgetInstance('current.client');
        app()->forgetInstance('current.store');
    }

    /**
     * Configura o banco de dados do tenant para o command
     */
    protected function configureTenantDatabaseForCommand($tenant): void
    {
        // Se a estratégia for banco separado, configura a conexão
        if (config('raptor.database.strategy') === 'separate') {
            $resolverClass = config('raptor.services.tenant_resolver', \Callcocam\LaravelRaptor\Services\TenantResolver::class);
            
            if (class_exists($resolverClass)) {
                $resolver = app($resolverClass);
                $resolver->configureTenantDatabase($tenant, null);
            }
        }
    }

    /**
     * Retorna o tenant atual configurado
     */
    protected function getCurrentTenant()
    {
        return app('tenant');
    }
}
