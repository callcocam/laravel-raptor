<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use App\Models\User;
use Callcocam\LaravelRaptor\Support\Concerns\HasCustomScopes;
use Callcocam\LaravelRaptor\Support\Landlord\BelongsToTenants;
use Callcocam\LaravelRaptor\Support\Sluggable\HasSlug;
use Callcocam\LaravelRaptor\Support\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class AbstractModel extends Model
{
    use HasSlug, HasUlids, BelongsToTenants, HasCustomScopes;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(
            config('raptor.landlord.models.tenant', Tenant::class),
            'tenant_id'
        );
    }
    /**
     * @return SlugOptions
     */
    public function getSlugOptions()
    {
        if (is_string($this->slugTo())) {
            return SlugOptions::create()
                ->generateSlugsFrom($this->slugFrom())
                ->saveSlugsTo($this->slugTo());
        }
    }
}
