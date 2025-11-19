<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Illuminate\Http\Request;

/**
 * Service responsável pela detecção de domínio e contexto de tenant/landlord.
 * 
 * Este service otimiza a detecção de contexto cachando resultados por request
 * e evitando múltiplas chamadas de configuração e processamento de strings.
 * 
 * @package Callcocam\LaravelRaptor\Services
 * @author Claudio Campos <callcocam@gmail.com>
 */
class DomainDetectionService
{
    /**
     * Cache interno para evitar reprocessamento durante o mesmo request
     */
    private static array $requestCache = [];

    /**
     * Configurações cachadas para evitar múltiplas chamadas config()
     */
    private static ?array $configCache = null;

    /**
     * Detecta se o request atual é de um tenant.
     *
     * Um tenant é identificado quando:
     * - O host não é o subdomínio do landlord
     * - O host não é o domínio base da aplicação
     * - O host não está na lista de domínios locais configurados
     *
     * @param Request|null $request Request a ser analisado (null usa o request atual)
     * @return bool True se for um tenant, false caso contrário
     */
    public function isTenant(?Request $request = null): bool
    {
        $request = $request ?: request();

        // Se não houver request disponível (ex: durante boot do service provider),
        // retorna false como fallback seguro
        if (!$request) {
            return false;
        }

        $cacheKey = 'is_tenant_' . $request->getHost();

        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }

        $host = $this->extractHostname($request->getHost());
        $config = $this->getConfigCache();

        // Verifica se é landlord
        if ($host === $config['landlord_subdomain']) {
            return self::$requestCache[$cacheKey] = false;
        }

        // Verifica se é o domínio base
        if ($host === $config['base_domain_host']) {
            return self::$requestCache[$cacheKey] = false;
        }

        // Verifica se está na lista de domínios locais
        if (in_array($host, $config['local_domains'], true)) {
            return self::$requestCache[$cacheKey] = false;
        }

        return self::$requestCache[$cacheKey] = true;
    }

    /**
     * Detecta se o request atual é do landlord.
     *
     * @param Request|null $request Request a ser analisado (null usa o request atual)
     * @return bool True se for landlord, false caso contrário
     */
    public function isLandlord(?Request $request = null): bool
    {
        $request = $request ?: request();

        // Se não houver request disponível (ex: durante boot do service provider),
        // retorna false como fallback seguro
        if (!$request) {
            return false;
        }

        $cacheKey = 'is_landlord_' . $request->getHost();

        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }

        $host = $this->extractHostname($request->getHost());
        $config = $this->getConfigCache();

        return self::$requestCache[$cacheKey] = $host === $config['landlord_subdomain'];
    }

    /**
     * Detecta se o request atual não é de um subdomínio.
     * 
     * @param Request|null $request Request a ser analisado (null usa o request atual)
     * @return bool True se não for subdomínio, false caso contrário
     */
    public function isNotSubdomain(?Request $request = null): bool
    {
        $request = $request ?: request();
        $cacheKey = 'is_not_subdomain_' . $request->getHost();

        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }

        $host = $this->extractHostname($request->getHost());
        $config = $this->getConfigCache();

        // Verifica se é exatamente o domínio base (sem subdomínio)
        $isBaseDomain = $host === $config['base_domain_host'];

        // Também considera domínios locais configurados como não-subdomínio
        $isLocalDomain = in_array($host, $config['local_domains'], true);

        return self::$requestCache[$cacheKey] = $isBaseDomain || $isLocalDomain;
    }

    /**
     * Detecta se o request atual é de um subdomínio.
     * 
     * @param Request|null $request Request a ser analisado (null usa o request atual)
     * @return bool True se for subdomínio, false caso contrário
     */
    public function isSubdomain(?Request $request = null): bool
    {
        return !$this->isNotSubdomain($request);
    }

    /**
     * Obtém o contexto atual (tenant, landlord ou base).
     * 
     * @param Request|null $request Request a ser analisado (null usa o request atual)
     * @return string Contexto: 'tenant', 'landlord' ou 'base'
     */
    public function getContext(?Request $request = null): string
    {
        $request = $request ?: request();
        $cacheKey = 'context_' . $request->getHost();

        if (isset(self::$requestCache[$cacheKey])) {
            return self::$requestCache[$cacheKey];
        }

        if ($this->isTenant($request)) {
            return self::$requestCache[$cacheKey] = 'tenant';
        }

        if ($this->isLandlord($request)) {
            return self::$requestCache[$cacheKey] = 'landlord';
        }

        return self::$requestCache[$cacheKey] = 'base';
    }

    /**
     * Extrai o hostname do host removendo protocolos e www.
     * 
     * @param string $host Host completo
     * @return string Hostname limpo
     */
    private function extractHostname(string $host): string
    {
        return str($host)
            ->replace(['http://', 'https://', 'www.'], '')
            ->explode('.')
            ->first();
    }

    /**
     * Obtém e cacheia as configurações necessárias para evitar múltiplas chamadas config().
     * 
     * @return array Configurações cachadas
     */
    private function getConfigCache(): array
    {
        if (self::$configCache !== null) {
            return self::$configCache;
        }

        $baseDomain = config('landlord.base_domain', config('app.url', ''));
        $cleanBaseDomain = str($baseDomain)
            ->replace(['http://', 'https://', 'www.'], '')
            ->explode('.')
            ->first();

        return self::$configCache = [
            'landlord_subdomain' => config('landlord.landlord_subdomain', 'landlord'),
            'base_domain_host' => $cleanBaseDomain,
            'local_domains' => config('landlord.local_domains', []),
        ];
    }

    /**
     * Limpa o cache interno (útil para testes).
     * 
     * @return void
     */
    public static function clearCache(): void
    {
        self::$requestCache = [];
        self::$configCache = null;
    }

    /**
     * Obtém informações de debug sobre o domínio atual.
     * 
     * @param Request|null $request Request a ser analisado (null usa o request atual)
     * @return array Informações de debug
     */
    public function getDebugInfo(?Request $request = null): array
    {
        $request = $request ?: request();
        
        return [
            'host' => $request->getHost(),
            'hostname' => $this->extractHostname($request->getHost()),
            'is_tenant' => $this->isTenant($request),
            'is_landlord' => $this->isLandlord($request),
            'is_subdomain' => $this->isSubdomain($request),
            'context' => $this->getContext($request),
            'config' => $this->getConfigCache(),
        ];
    }
}