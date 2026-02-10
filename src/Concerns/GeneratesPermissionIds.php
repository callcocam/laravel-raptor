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
     * Gera um ULID determinístico baseado no slug da permissão.
     * Compatível com a coluna ulid('id') da tabela permissions.
     * O mesmo slug sempre gera o mesmo ID (útil para sync entre ambientes).
     */
    protected function generateDeterministicId(string $slug): string
    {
        $hash = md5($slug);
        $prefix = 'PM'; // Permission
        $hashComponent = strtoupper(substr($hash, 0, 24));

        return $prefix.$hashComponent;
    }
}
