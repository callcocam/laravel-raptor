<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use Callcocam\LaravelRaptor\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends AbstractModel
{
    use SoftDeletes;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        static::$landlord->disable();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'domain',
        'database',
        'prefix',
        'email',
        'phone',
        'document',
        'logo',
        'settings',
        'status',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => TenantStatus::class,
        'settings' => 'array',
    ];



    /**
     * Relacionamento: Tenant tem muitos usuários
     */
    public function users(): HasMany
    {
        return $this->hasMany(
            config('raptor.models.user', \Callcocam\LaravelRaptor\Models\Auth\User::class)
        );
    }

    /**
     * Relacionamento: Tenant tem muitos domínios
     */
    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    /**
     * Retorna o domínio primário do tenant.
     */
    public function getPrimaryDomain(): ?TenantDomain
    {
        return $this->domains()->where('is_primary', true)->first();
    }

    /**
     * Retorna todos os domínios secundários do tenant.
     */
    public function getSecondaryDomains()
    {
        return $this->domains()->where('is_primary', false)->get();
    }

    /**
     * Retorna todos os domínios do tenant como array de strings.
     */
    public function getAllDomainsList(): array
    {
        return $this->domains()->pluck('domain')->toArray();
    }

    /**
     * Scope: Apenas tenants ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', TenantStatus::Published);
    }


    /**
     * Verifica se o tenant está ativo
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::Published;
    }

    /**
     * Verifica se o tenant possui domínio customizado
     * 
     * @deprecated Use domains() relationship instead
     */
    public function hasCustomDomain(): bool
    {
        return $this->domains()->exists();
    }

    /**
     * Retorna a URL completa do tenant
     */
    public function getUrl(): string
    {
        $primaryDomain = $this->getPrimaryDomain();
        
        if ($primaryDomain) {
            return 'https://' . $primaryDomain->domain;
        }

        // Fallback para compatibilidade
        $mainDomain = config('raptor.main_domain', 'localhost');
        return 'https://' . ($this->subdomain ?? $this->slug) . '.' . $mainDomain;
    }
}
