<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TenantDomain extends AbstractModel
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        static::$landlord->disable();
    }
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tenant_domains';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'domain',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Boot method - garante regras de negócio.
     */
    protected static function boot()
    {
        parent::boot();

        // Antes de criar, valida o domínio
        static::creating(function (TenantDomain $model) {
            $model->validateDomain();
            $model->ensureSinglePrimaryDomain();
        });

        // Antes de atualizar, valida mudanças
        static::updating(function (TenantDomain $model) {
            if ($model->isDirty('domain')) {
                $model->validateDomain();
            }

            if ($model->isDirty('is_primary') && $model->is_primary) {
                $model->ensureSinglePrimaryDomain();
            }
        });
    }

    /**
     * Relacionamento: Domínio pertence a um tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            config('raptor.models.tenant', Tenant::class),
            'tenant_id'
        );
    }

    /**
     * Valida formato do domínio.
     */
    protected function validateDomain(): void
    {
        $validator = Validator::make(
            ['domain' => $this->domain],
            [
                'domain' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i',
                    function ($attribute, $value, $fail) {
                        // Verifica unicidade ignorando o próprio registro
                        $exists = static::where('domain', $value)
                            ->when($this->exists, fn($q) => $q->where('id', '!=', $this->id))
                            ->exists();

                        if ($exists) {
                            $fail('Este domínio já está sendo utilizado por outro tenant.');
                        }
                    },
                ],
            ],
            [
                'domain.required' => 'O domínio é obrigatório.',
                'domain.regex' => 'O domínio informado não é válido. Use formato como: exemplo.com.br',
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Garante que apenas um domínio seja primário por tenant.
     */
    protected function ensureSinglePrimaryDomain(): void
    {
        if ($this->is_primary && $this->tenant_id) {
            // Remove flag is_primary dos outros domínios do mesmo tenant
            static::where('tenant_id', $this->tenant_id)
                ->where('id', '!=', $this->id ?? '')
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }
    }

    /**
     * Scope: Apenas domínios primários.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Apenas domínios secundários.
     */
    public function scopeSecondary($query)
    {
        return $query->where('is_primary', false);
    }

    /**
     * Normaliza domínio removendo www e convertendo para lowercase.
     */
    public function setDomainAttribute(?string $value): void
    {
        if ($value) {
            $normalized = strtolower(trim($value));
            $normalized = str_replace('www.', '', $normalized);
            $this->attributes['domain'] = $normalized;
        } else {
            $this->attributes['domain'] = null;
        }
    }

    protected function slugTo()
    {
        return true;
    }
}
