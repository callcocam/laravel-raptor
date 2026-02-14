<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Contracts;

use Illuminate\Http\Request;

/**
 * Interface para resolver tenant
 *
 * Implemente esta interface para criar sua própria lógica de resolução de tenant.
 * Útil quando você precisa resolver tenant por Client, Store, ou outra entidade.
 *
 * @example
 * ```php
 * // No seu projeto, crie sua implementação:
 * class MyTenantResolver implements TenantResolverInterface
 * {
 *     public function resolve(Request $request): mixed
 *     {
 *         // Sua lógica customizada aqui
 *         // Pode resolver por Client, Store, etc.
 *     }
 * }
 *
 * // Configure no config/raptor.php:
 * 'services' => [
 *     'tenant_resolver' => \App\Services\MyTenantResolver::class,
 * ]
 * ```
 */
interface TenantResolverInterface
{
    /**
     * Resolve e configura tenant baseado no request
     *
     * Este método é chamado no início de cada requisição para identificar
     * o tenant atual. A implementação deve:
     *
     * 1. Detectar o tenant baseado no domínio/subdomínio
     * 2. Configurar o contexto da aplicação (config, app instances)
     * 3. Configurar banco de dados se necessário
     * 4. Retornar a instância do tenant ou null
     *
     * @param  Request  $request  O request HTTP atual
     * @return mixed O tenant resolvido ou null se não encontrado
     */
    public function resolve(Request $request): mixed;

    /**
     * Retorna o tenant já resolvido (ou null)
     *
     * Útil para acessar o tenant após a resolução inicial
     * sem precisar resolver novamente.
     *
     * @return mixed O tenant ou null
     */
    public function getTenant(): mixed;

    /**
     * Verifica se o tenant já foi resolvido nesta requisição
     *
     * @return bool True se já foi resolvido
     */
    public function isResolved(): bool;

    /**
     * Armazena o contexto do tenant no container
     *
     * Configura as instâncias e configs necessárias para o tenant funcionar.
     *
     * @param  mixed  $tenant  A instância do tenant
     * @param  object|null  $domainData  Dados adicionais do domínio (domainable_type, domainable_id, etc)
     */
    public function storeTenantContext(mixed $tenant, ?object $domainData = null): void;

    /**
     * Configura o banco de dados do tenant
     *
     * Para estratégias de banco separado por tenant.
     *
     * @param  mixed  $tenant  A instância do tenant
     * @param  object|null  $domainData  Dados adicionais do domínio
     */
    public function configureTenantDatabase(mixed $tenant, ?object $domainData = null): void;
}
