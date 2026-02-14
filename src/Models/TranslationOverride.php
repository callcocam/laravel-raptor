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

class TranslationOverride extends Model
{
    use HasUlids;
    use UsesLandlordConnection;

    protected $fillable = [
        'translation_group_id',
        'key',
        'value',
    ];

    public function getTable(): string
    {
        return config('raptor.tables.translation_overrides', 'translation_overrides');
    }

    /**
     * Relacionamento: TranslationOverride pertence a um TranslationGroup
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(
            config('raptor.landlord.models.translation_group', TranslationGroup::class),
            'translation_group_id'
        );
    }

    /**
     * Accessor: Acessa tenant_id via relacionamento group
     */
    public function getTenantIdAttribute(): ?string
    {
        return $this->group?->tenant_id;
    }

    /**
     * Accessor: Acessa group (nome do grupo) via relacionamento
     */
    public function getGroupNameAttribute(): ?string
    {
        return $this->group?->group;
    }

    /**
     * Accessor: Acessa locale via relacionamento group
     */
    public function getLocaleAttribute(): ?string
    {
        return $this->group?->locale;
    }

    /**
     * Retorna a chave completa da tradução (group.key)
     */
    public function getFullKeyAttribute(): string
    {
        $group = $this->group?->group;

        return $group ? "{$group}.{$this->key}" : $this->key;
    }
}
