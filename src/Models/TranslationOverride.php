<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranslationOverride extends AbstractModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'translation_overrides';

    /**
     * Indica que este model não usa ULID como primary key
     * (usa auto-increment ID padrão)
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'group',
        'key',
        'locale',
        'value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Boot do model - desabilita o landlord para este model
     * pois ele gerencia traduções globais e por tenant
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Define a tabela dinamicamente do config
        $this->table = config('raptor.tables.translation_overrides', 'translation_overrides');

        // Desabilita landlord para este model permitir traduções globais
        static::$landlord->disable();
    }

    /**
     * Relacionamento: TranslationOverride pertence a um Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            config('raptor.landlord.models.tenant', Tenant::class),
            'tenant_id'
        );
    }

    /**
     * Scope: Apenas traduções globais (tenant_id NULL)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Scope: Traduções de um tenant específico
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Busca por grupo, key e locale
     */
    public function scopeByTranslation($query, ?string $group, string $key, string $locale)
    {
        return $query
            ->where('group', $group)
            ->where('key', $key)
            ->where('locale', $locale);
    }

    /**
     * Scope: Busca por locale
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Verifica se é uma tradução global
     */
    public function isGlobal(): bool
    {
        return $this->tenant_id === null;
    }

    /**
     * Retorna a chave completa da tradução (group.key)
     */
    public function getFullKey(): string
    {
        return $this->group ? "{$this->group}.{$this->key}" : $this->key;
    }

    /**
     * Método estático para buscar uma tradução override
     */
    public static function findOverride(?string $tenantId, ?string $group, string $key, string $locale): ?self
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->byTranslation($group, $key, $locale)
            ->first();
    }

    /**
     * Método estático para buscar tradução com fallback
     * Prioridade: Tenant > Global DB > null
     */
    public static function getTranslation(?string $tenantId, ?string $group, string $key, string $locale): ?string
    {
        // Tenta buscar override do tenant
        if ($tenantId) {
            $tenantOverride = static::findOverride($tenantId, $group, $key, $locale);
            if ($tenantOverride) {
                return $tenantOverride->value;
            }
        }

        // Fallback: busca tradução global em DB
        $globalOverride = static::findOverride(null, $group, $key, $locale);

        return $globalOverride?->value;
    }
}
