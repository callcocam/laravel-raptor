<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Database\Factories;

use Callcocam\LaravelRaptor\Models\Tenant;
use Callcocam\LaravelRaptor\Models\TranslationOverride;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranslationOverride>
 */
class TranslationOverrideFactory extends Factory
{
    protected $model = TranslationOverride::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $groups = ['products', 'cart', 'checkout', 'auth', 'navigation', 'messages'];
        $keys = ['title', 'description', 'button', 'label', 'placeholder', 'message'];
        $locales = ['pt_BR', 'en', 'es', 'fr'];

        return [
            'tenant_id' => null, // Por padrão cria tradução global
            'group' => fake()->randomElement($groups),
            'key' => fake()->randomElement($keys),
            'locale' => fake()->randomElement($locales),
            'value' => fake()->sentence(),
        ];
    }

    /**
     * State: Tradução para um tenant específico
     */
    public function forTenant(?string $tenantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId ?? Tenant::factory()->create()->id,
        ]);
    }

    /**
     * State: Tradução global (tenant_id NULL)
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
        ]);
    }

    /**
     * State: Tradução em Português do Brasil
     */
    public function ptBR(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'pt_BR',
        ]);
    }

    /**
     * State: Tradução em Inglês
     */
    public function en(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'en',
        ]);
    }

    /**
     * State: Tradução em Espanhol
     */
    public function es(): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => 'es',
        ]);
    }

    /**
     * State: Tradução para grupo específico
     */
    public function forGroup(string $group): static
    {
        return $this->state(fn (array $attributes) => [
            'group' => $group,
        ]);
    }

    /**
     * State: Tradução para key específica
     */
    public function forKey(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
        ]);
    }

    /**
     * State: Tradução completa customizada
     */
    public function custom(?string $group, string $key, string $locale, string $value, ?string $tenantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
            'group' => $group,
            'key' => $key,
            'locale' => $locale,
            'value' => $value,
        ]);
    }
}
