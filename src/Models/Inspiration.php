<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Callcocam\LaravelRaptor\Support\Landlord\UsesLandlordConnection;

class Inspiration extends AbstractModel
{
    use HasFactory;
    use SoftDeletes;
    use UsesLandlordConnection;
    
    protected function slugTo()
    {
        return false;
    }
}
