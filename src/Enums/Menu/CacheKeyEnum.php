<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Enums\Menu;

enum CacheKeyEnum: string
{
    case LANDLORD_MENUS = 'papa_leguas_menu_landlord_menus';
    case TENANT_MENUS = 'papa_leguas_menu_tenant_menus';
    case LANDLORD_NAVIGATION = 'papa_leguas_menu_landlord_navigation';
    case TENANT_NAVIGATION = 'papa_leguas_menu_tenant_navigation';

    public static function forContext(ContextEnum $context, string $type = 'menus'): string
    {
        return match ([$context, $type]) {
            [ContextEnum::LANDLORD, 'menus'] => self::LANDLORD_MENUS->value,
            [ContextEnum::TENANT, 'menus'] => self::TENANT_MENUS->value,
            [ContextEnum::LANDLORD, 'navigation'] => self::LANDLORD_NAVIGATION->value,
            [ContextEnum::TENANT, 'navigation'] => self::TENANT_NAVIGATION->value,
            default => "papa_leguas_menu_{$context->value}_{$type}",
        };
    }

    public function getTtl(): int
    {
        return config('laravel-raptor-menu.cache.ttl', 86400);
    }
}
