<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Landlord;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait para models que sempre devem usar o banco principal (landlord).
 *
 * A conexão usada é a mesma config da default, apenas com o nome do banco
 * banco principal (conexão definida em config/database.php).
 * O registro dessa conexão é feito no LandlordServiceProvider.
 *
 * IMPORTANTE: Não alteramos a database da conexão default aqui, pois em
 * contexto tenant a default aponta para o banco do tenant; usamos uma
 * conexão separada (landlord) que sempre aponta para o banco principal.
 *
 * @mixin Model
 */
trait UsesLandlordConnection
{
    /**
     * Sempre usa a conexão do banco principal (configurada no provider).
     */
    public function getConnectionName(): ?string
    {
        return config('raptor.database.landlord_connection_name', 'landlord');
    }
}
