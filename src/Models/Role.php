<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use Callcocam\LaravelRaptor\Support\Landlord\UsesLandlordConnection;
use Callcocam\LaravelRaptor\Support\Shinobi\Models\Role as ModelsRole;

class Role extends ModelsRole
{
    use UsesLandlordConnection;
}
