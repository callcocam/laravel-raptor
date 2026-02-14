<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use Callcocam\LaravelRaptor\Support\Landlord\UsesLandlordConnection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TranslationGroup extends Model
{
    use HasUlids;
    use UsesLandlordConnection;

    protected $fillable = [
        'tenant_id',
        'name',
        'locale',
    ];

    protected $with = ['overrides'];

    public function getTable(): string
    {
        return config('raptor.tables.translation_groups', 'translation_groups');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            config('raptor.landlord.models.tenant', Tenant::class),
            'tenant_id'
        );
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(
            config('raptor.landlord.models.translate', TranslationOverride::class),
            'translation_group_id'
        );
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
