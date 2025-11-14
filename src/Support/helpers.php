<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

if (!function_exists('current_tenant')) {
    /**
     * Retorna o tenant atual
     */
    function current_tenant(): ?\Callcocam\LaravelRaptor\Models\Tenant
    {
        return app('current.tenant');
    }
}

if (!function_exists('tenant_id')) {
    /**
     * Retorna o ID do tenant atual
     */
    function tenant_id(): ?string
    {
        return config('app.current_tenant_id');
    }
}

if (!function_exists('is_landlord')) {
    /**
     * Verifica se está no contexto landlord
     */
    function is_landlord(): bool
    {
        return config('app.context') === 'landlord';
    }
}

if (!function_exists('is_tenant')) {
    /**
     * Verifica se está no contexto tenant
     */
    function is_tenant(): bool
    {
        return config('app.context') === 'tenant';
    }
}

if (!function_exists('is_site')) {
    /**
     * Verifica se está no contexto do site principal
     */
    function is_site(): bool
    {
        return config('app.context') === 'site';
    }
}

if (!function_exists('tenant_url')) {
    /**
     * Gera URL para um tenant específico
     */
    function tenant_url(string $subdomain, string $path = ''): string
    {
        $mainDomain = config('raptor.main_domain', 'localhost');
        $protocol = request()->secure() ? 'https' : 'http';
        
        return "{$protocol}://{$subdomain}.{$mainDomain}" . ($path ? "/{$path}" : '');
    }
}

if (!function_exists('landlord_url')) {
    /**
     * Gera URL para o landlord
     */
    function landlord_url(string $path = ''): string
    {
        $mainDomain = config('raptor.main_domain', 'localhost');
        $landlordSubdomain = config('raptor.landlord.subdomain', 'landlord');
        $protocol = request()->secure() ? 'https' : 'http';
        
        return "{$protocol}://{$landlordSubdomain}.{$mainDomain}" . ($path ? "/{$path}" : '');
    }
}

if (!function_exists('site_url')) {
    /**
     * Gera URL para o site principal
     */
    function site_url(string $path = ''): string
    {
        $mainDomain = config('raptor.main_domain', 'localhost');
        $protocol = request()->secure() ? 'https' : 'http';
        
        return "{$protocol}://{$mainDomain}" . ($path ? "/{$path}" : '');
    }
}
