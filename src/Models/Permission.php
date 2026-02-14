<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use Callcocam\LaravelRaptor\Support\Shinobi\Models\Permission as ModelsPermission;
use Callcocam\LaravelRaptor\Support\Landlord\UsesLandlordConnection;

class Permission extends ModelsPermission {
    use UsesLandlordConnection;
}
