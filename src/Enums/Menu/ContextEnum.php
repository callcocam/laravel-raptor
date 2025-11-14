<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Enums\Menu;

enum ContextEnum: string
{
    case LANDLORD = 'landlord';
    case TENANT = 'tenant';

    /**
     * Obtém o namespace do controller para o contexto
     */
    public function getNamespace(): string
    {
        return match ($this) {
            self::LANDLORD => 'App\Http\Controllers\Api\Landlord',
            self::TENANT => 'App\Http\Controllers\Api\Tenant',
        };
    }

    /**
     * Obtém o caminho dos controllers para o contexto
     */
    public function getPath(): string
    { 
        return match ($this) {
            self::LANDLORD =>  '/Landlord',
            self::TENANT =>  '/Tenant',
        };
    }

    /**
     * Obtém o prefixo das rotas para o contexto
     */
    public function getRoutePrefix(): string
    {
        return match ($this) {
            self::LANDLORD => 'api/landlord',
            self::TENANT => 'api/tenant',
        };
    }

    /**
     * Obtém o name da rota para o contexto
     */
    public function getRouteNames(): string
    {
        return match ($this) {
            self::LANDLORD => 'api.landlord.',
            self::TENANT => 'api.tenant.',
        };
    }

    /**
     * Retorna todos os contextos disponíveis
     */
    public static function all(): array
    {
        return [
            self::LANDLORD,
            self::TENANT,
        ];
    }

    /**
     * Obtém o label amigável do contexto
     */
    public function label(): string
    {
        return match ($this) {
            self::LANDLORD => 'Landlord',
            self::TENANT => 'Tenant',
        };
    }
}
