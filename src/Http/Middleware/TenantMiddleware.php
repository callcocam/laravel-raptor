<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Middleware;

use Callcocam\LaravelRaptor\Enums\TenantStatus; 
use Callcocam\LaravelRaptor\Services\TenantConnectionService;
use Callcocam\LaravelRaptor\Support\Landlord\Facades\Landlord;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $domain = str($host)->replace('www.', '')->toString();

        $tenantModel = config('raptor.models.tenant', \Callcocam\LaravelRaptor\Models\Tenant::class);
        
        // Busca domínio com tenant e domainable em uma query otimizada
        $domainData = DB::table('tenant_domains')
            ->join('tenants', 'tenants.id', '=', 'tenant_domains.tenant_id')
            ->where('tenant_domains.domain', $domain)
            ->where('tenants.status', TenantStatus::Published->value)
            ->whereNull('tenants.deleted_at')
            ->select(
                'tenants.*',
                'tenant_domains.domainable_type',
                'tenant_domains.domainable_id',
                'tenant_domains.is_primary'
            )
            ->first();

        // Fallback: busca por coluna 'domain' se tenant_domains estiver vazio (retrocompatibilidade)
        if (!$domainData) {
            $domainColumn = config('raptor.tenant.subdomain_column', 'domain');
            $tenant = $tenantModel::where($domainColumn, $domain)->first();
            
            if (!$tenant) {
                abort(404, 'Tenant não encontrado.');
            }
            
            $domainData = (object) [
                'id' => $tenant->id,
                'domainable_type' => null,
                'domainable_id' => null,
                'is_primary' => true,
            ];
        }
        
        // Converte para model instance
        $tenant = $tenantModel::find($domainData->id);
        
        if (!$tenant || $tenant->status !== TenantStatus::Published) {
            abort(403, 'Este tenant está inativo.');
        }
 
        // Armazena contexto do tenant
        app()->instance('tenant.context', true);
        app()->instance('current.tenant', $tenant);
        config(['app.context' => 'tenant']);
        config(['app.current_tenant_id' => $tenant->id]);

        // Se domínio tem domainable (Client, Store, etc), armazena no contexto
        if ($domainData->domainable_type && $domainData->domainable_id) {
            $dominableClass = $domainData->domainable_type;
            $domainable = $dominableClass::find($domainData->domainable_id);
            
            if ($domainable) {
                app()->instance('current.domainable', $domainable);
                app()->instance('current.domainable_type', $domainData->domainable_type);
                app()->instance('current.domainable_id', $domainData->domainable_id);
                
                // Configs úteis para queries
                config(['app.current_domainable_type' => $domainData->domainable_type]);
                config(['app.current_domainable_id' => $domainData->domainable_id]);
                
                // Exemplo: se for Client
                if ($domainData->domainable_type === 'App\\Models\\Client') {
                    config(['app.current_client_id' => $domainData->domainable_id]);
                }
                
                // Exemplo: se for Store
                if ($domainData->domainable_type === 'App\\Models\\Store') {
                    config(['app.current_store_id' => $domainData->domainable_id]);
                }
            }
        }

        Landlord::addTenant($tenant);

        // Configura conexão de banco de dados seguindo a hierarquia: Store > Client > Tenant > Default
        app(TenantConnectionService::class)->configureTenantDatabase($tenant, $domainData);

        // Se houver usuário autenticado, verifica se ele pertence a este tenant
        if ($request->user() && $request->user()->tenant_id !== $tenant->id) {
            auth()->logout();
            abort(403, 'Acesso negado. Você não tem permissão para acessar este tenant.');
        }

        return $next($request);
    }
}
