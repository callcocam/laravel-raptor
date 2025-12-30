<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Enums;

enum AddressStatus: string
{
    case Draft = 'draft';
    case Published = 'published'; 
    case IsDefault = 'is_default';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Published => 'Publicado', 
            self::IsDefault => 'Padrão',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'green', 
            self::IsDefault => 'blue',
        };
    }
}
