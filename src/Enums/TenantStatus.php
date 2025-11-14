<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Enums;

enum TenantStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Published => 'Publicado',
            self::Suspended => 'Suspenso',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'green',
            self::Suspended => 'orange',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()])
            ->values()
            ->toArray();
    }

    public static function getOptions(): array
    {
        return [
            self::Published->value => self::Published->label(),
            self::Draft->value => self::Draft->label(),
            self::Suspended->value => self::Suspended->label(),
        ];
    }

    public static function variantOptions(): array
    {
        return [
            self::Published->value => 'success',
            self::Draft->value => 'secondary',
            self::Suspended->value => 'warning',
        ];
    }
}
