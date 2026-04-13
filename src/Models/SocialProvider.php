<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use Callcocam\LaravelRaptor\Support\Landlord\UsesLandlordConnection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialProvider extends AbstractModel
{
    use SoftDeletes;
    use UsesLandlordConnection;

    protected $table = 'social_providers';

    protected function casts(): array
    {
        return [
            'scopes'        => 'array',
            'client_secret' => 'encrypted',
        ];
    }

    /**
     * Providers suportados nativamente pelo Socialite.
     *
     * @return string[]
     */
    public static function availableProviders(): array
    {
        return ['google', 'facebook', 'github', 'twitter', 'linkedin', 'microsoft', 'azure'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            config('raptor.landlord.models.tenant', Tenant::class)
        );
    }
}
