<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Database\Factories;

use Callcocam\LaravelRaptor\Models\TranslationGroup;
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
        return [
            'translation_group_id' => TranslationGroup::factory(),
            'key' => $this->faker->word(),
            'value' => $this->faker->sentence(),
        ];
    }

    /**
     * State: Tradução para um grupo específico
     */
    public function forGroup(string|TranslationGroup $group): static
    {
        return $this->state(fn (array $attributes) => [
            'translation_group_id' => is_string($group) ? $group : $group->id,
        ]);
    }
}
