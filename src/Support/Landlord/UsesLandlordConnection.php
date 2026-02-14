<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Trait para models que sempre devem usar o banco principal (landlord).
 *
 * Garante que o model nunca use a conexão do tenant, mesmo em contexto tenant.
 * Útil para: User, Tenant, Role, Permission, TranslationGroup e demais models
 * do pacote que vivem no banco principal.
 *
 * @mixin Model
 */
trait UsesLandlordConnection
{
    /**
     * Garante que o model use sempre a conexão do banco principal.
     *
     * IMPORTANTE: Models com esta trait nunca usam o banco do tenant.
     */
    public function getConnectionName(): ?string
    {
        $this->ensureLandlordConnection();

        return config('raptor.database.landlord_connection_name', 'landlord');
    }

    /**
     * Garante que a conexão 'landlord' existe e aponta para o banco principal.
     *
     * Se a conexão não estiver definida (ex.: em config/database.php), cria uma
     * baseada na conexão default, usando o nome do banco em raptor.database.landlord_database.
     */
    protected function ensureLandlordConnection(): void
    {
        $connectionName = config('raptor.database.landlord_connection_name', 'landlord');

        if (config()->has("database.connections.{$connectionName}")) {
            return;
        }

        $defaultConnection = config('database.default');
        $baseConfig = config("database.connections.{$defaultConnection}");
        $landlordDatabase = config('raptor.database.landlord_database');

        if (! is_array($baseConfig) || empty($landlordDatabase)) {
            return;
        }

        $landlordConfig = array_merge($baseConfig, [
            'database' => $landlordDatabase,
        ]);

        config(["database.connections.{$connectionName}" => $landlordConfig]);
        DB::purge($connectionName);
    }
}
