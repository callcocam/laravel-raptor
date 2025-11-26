<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Database\Factories;

use Callcocam\LaravelRaptor\Models\Tenant;
use Callcocam\LaravelRaptor\Models\TranslationGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class TranslationGroupFactory extends Factory
{
    protected $model = TranslationGroup::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null, // Global por padrão
            'group' => $this->faker->randomElement(['products', 'cart', 'checkout', 'auth', 'navigation']),
            'locale' => $this->faker->randomElement(['pt_BR', 'en', 'es', 'fr']),
        ];
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
        ]);
    }

    public function forTenant(?string $tenantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId ?? Tenant::factory()->create()->id,
        ]);
    }

    public function ptBR(): static
    {
        return $this->state(fn (array $attributes) => ['locale' => 'pt_BR']);
    }

    public function en(): static
    {
        return $this->state(fn (array $attributes) => ['locale' => 'en']);
    }
}
