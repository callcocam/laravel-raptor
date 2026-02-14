<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use Callcocam\LaravelRaptor\Models\AbstractModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Callcocam\LaravelRaptor\Support\Landlord\UsesLandlordConnection;

class Image extends AbstractModel {
    use SoftDeletes;
    use UsesLandlordConnection;
}
