<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Concerns;

trait GeneratesPermissionIds
{
    /**
     * Gera um ID determinístico baseado no slug da permissão.
     * Isso garante que a mesma permissão sempre terá o mesmo ID.
     * 
     * Usa CRC32 para gerar um hash numérico consistente.
     */
    protected function generateDeterministicId(string $slug): int
    {
        // Usa crc32 para gerar um hash numérico do slug
        // Como crc32 pode retornar valores negativos, usamos abs() e limitamos
        return abs(crc32($slug));
    }
}
