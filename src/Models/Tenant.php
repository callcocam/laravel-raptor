<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use Callcocam\LaravelRaptor\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends AbstractModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',  
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
     */
    public function hasCustomDomain(): bool
    {
        return ! empty($this->custom_domain);
    }

    /**
     * Retorna a URL completa do tenant
     */
    public function getUrl(): string
    {
        if ($this->hasCustomDomain()) {
            return 'https://'.$this->custom_domain;
        }

        $mainDomain = config('raptor.main_domain', 'localhost');

        return 'https://'.$this->subdomain.'.'.$mainDomain;
    }
}
