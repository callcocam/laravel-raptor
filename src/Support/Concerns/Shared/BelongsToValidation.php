<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToValidation
{
    protected bool $required = false;

    protected array|string|Closure $rules = [];

    protected array $messages = [];

    /**
     * Marca o campo como obrigatório
     */
    public function required(bool $required = true): static
    {
        $this->required = $required;

        // Adiciona 'required' às rules se não existir
        if ($required && !$this->hasRule('required')) {
            $this->addRule('required');
        }

        return $this;
    }

    /**
     * Define as regras de validação do campo
     */
    public function rules(array|string|Closure $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Adiciona uma regra de validação
     */
    public function addRule(string $rule): static
    {
        if (is_string($this->rules)) {
            $this->rules .= '|' . $rule;
        } elseif (is_array($this->rules)) {
            $this->rules[] = $rule;
        } else {
            $this->rules = [$rule];
        }

        return $this;
    }

    /**
     * Define mensagens de validação customizadas
     */
    public function messages(array $messages): static
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Verifica se o campo é obrigatório
     */
    public function isRequired(): bool
    {
        return $this->required || $this->hasRule('required');
    }

    /**
     * Retorna as regras de validação
     */
    public function getRules(): array|string
    {
        return $this->evaluate($this->rules);
    }

    /**
     * Retorna as mensagens de validação
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Verifica se uma regra específica existe
     */
    protected function hasRule(string $rule): bool
    {
        $rules = $this->getRules();

        if (is_string($rules)) {
            return str_contains($rules, $rule);
        }

        if (is_array($rules)) {
            return in_array($rule, $rules) || in_array($rule, array_map('strval', $rules));
        }

        return false;
    }
}
