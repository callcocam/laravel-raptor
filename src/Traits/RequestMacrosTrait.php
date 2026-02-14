<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Traits;

use Callcocam\LaravelRaptor\Services\DomainDetectionService;
use Illuminate\Http\Request;

/**
 * Trait para registrar Request Macros otimizados.
 *
 * Este trait fornece macros para o Request que utilizam o DomainDetectionService
 * para detecção otimizada de contexto tenant/landlord, evitando processamento
 * duplicado e melhorando a performance.
 *
 * @author Claudio Campos <callcocam@gmail.com>
 */
trait RequestMacrosTrait
{
    /**
     * Registra todos os Request Macros otimizados.
     *
     * Este método deve ser chamado durante o boot do service provider
     * para registrar os macros no Request.
     */
    protected function registerRequestMacros(): void
    {
        $this->registerTenantMacros();
        $this->registerLandlordMacros();
        $this->registerSubdomainMacros();
        $this->registerContextMacros();
    }

    /**
     * Registra macros relacionados a tenant.
     */
    private function registerTenantMacros(): void
    {
        /**
         * Verifica se o request atual é de um tenant.
         *
         * @return bool True se for tenant, false caso contrário
         */
        Request::macro('isTenant', function (): bool {
            return app(DomainDetectionService::class)->isTenant($this);
        });
    }

    /**
     * Registra macros relacionados a landlord.
     */
    private function registerLandlordMacros(): void
    {
        /**
         * Verifica se o request atual é do landlord.
         *
         * @return bool True se for landlord, false caso contrário
         */
        Request::macro('isLandlord', function (): bool {
            return app(DomainDetectionService::class)->isLandlord($this);
        });
    }

    /**
     * Registra macros relacionados a subdomínio.
     */
    private function registerSubdomainMacros(): void
    {
        /**
         * Verifica se o request atual não é de um subdomínio.
         *
         * @return bool True se não for subdomínio, false caso contrário
         */
        Request::macro('isNotSubdomain', function (): bool {
            return app(DomainDetectionService::class)->isNotSubdomain($this);
        });

        /**
         * Verifica se o request atual é de um subdomínio.
         *
         * @return bool True se for subdomínio, false caso contrário
         */
        Request::macro('isSubdomain', function (): bool {
            return app(DomainDetectionService::class)->isSubdomain($this);
        });
    }

    /**
     * Registra macros relacionados a contexto.
     */
    private function registerContextMacros(): void
    {
        /**
         * Obtém o contexto atual do request.
         *
         * @return string Contexto: 'tenant', 'landlord' ou 'base'
         */
        Request::macro('getContext', function (): string {
            return app(DomainDetectionService::class)->getContext($this);
        });

        /**
         * Obtém informações de debug sobre o domínio atual.
         *
         * @return array Informações de debug incluindo host, contexto e configurações
         */
        Request::macro('getDomainDebugInfo', function (): array {
            return app(DomainDetectionService::class)->getDebugInfo($this);
        });
    }
}
