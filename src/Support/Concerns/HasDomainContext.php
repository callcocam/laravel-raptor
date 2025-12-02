<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasDomainContext
 * 
 * Facilita o acesso ao contexto de domínio polimórfico (tenant + domainable)
 * 
 * @example
 * class OrderController extends Controller
 * {
 *     use HasDomainContext;
 *     
 *     public function index()
 *     {
 *         $tenant = $this->getCurrentTenant();
 *         $client = $this->getCurrentDomainable(); // Client ou Store
 *         
 *         if ($this->hasDomainable()) {
 *             // Filtrar por client/store
 *             $orders = Order::where('client_id', $client->id)->get();
 *         } else {
 *             // Domínio principal do tenant
 *             $orders = Order::all();
 *         }
 *     }
 * }
 */
trait HasDomainContext
{
    /**
     * Retorna o tenant atual do contexto
     */
    protected function getCurrentTenant(): ?Model
    {
        return app('current.tenant');
    }

    /**
     * Retorna o tenant ID atual
     */
    protected function getCurrentTenantId(): ?string
    {
        return config('app.current_tenant_id');
    }

    /**
     * Retorna o domainable atual (Client, Store, etc) se existir
     */
    protected function getCurrentDomainable(): ?Model
    {
        return app()->has('current.domainable') 
            ? app('current.domainable') 
            : null;
    }

    /**
     * Retorna o ID do domainable atual
     */
    protected function getCurrentDominableId(): ?string
    {
        return config('app.current_domainable_id');
    }

    /**
     * Retorna o tipo (class) do domainable atual
     */
    protected function getCurrentDominableType(): ?string
    {
        return config('app.current_domainable_type');
    }

    /**
     * Verifica se o contexto atual tem um domainable
     */
    protected function hasDomainable(): bool
    {
        return !is_null($this->getCurrentDomainable());
    }

    /**
     * Verifica se o domainable é de um tipo específico
     */
    protected function isDominableType(string $type): bool
    {
        $currentType = $this->getCurrentDominableType();
        return $currentType && $currentType === $type;
    }

    /**
     * Retorna o Client atual (se domainable for Client)
     */
    protected function getCurrentClient(): ?Model
    {
        if ($this->isDominableType('App\\Models\\Client')) {
            return $this->getCurrentDomainable();
        }
        return null;
    }

    /**
     * Retorna o Store atual (se domainable for Store)
     */
    protected function getCurrentStore(): ?Model
    {
        if ($this->isDominableType('App\\Models\\Store')) {
            return $this->getCurrentDomainable();
        }
        return null;
    }

    /**
     * Retorna dados completos do contexto de domínio
     */
    protected function getDomainContext(): array
    {
        return [
            'tenant' => $this->getCurrentTenant(),
            'tenant_id' => $this->getCurrentTenantId(),
            'domainable' => $this->getCurrentDomainable(),
            'domainable_id' => $this->getCurrentDominableId(),
            'domainable_type' => $this->getCurrentDominableType(),
            'has_domainable' => $this->hasDomainable(),
        ];
    }
}
