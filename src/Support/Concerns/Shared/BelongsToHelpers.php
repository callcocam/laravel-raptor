<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Closure;

trait BelongsToHelpers
{
    protected mixed $default = '';

    protected string|Closure|null $helpText = null;

    protected string|array|Closure|AbstractColumn|null $hint = null;

    protected string|array|Closure|null $prepend = null;

    protected string|array|Closure|null $append = null;

    protected ?string $prefix = null;

    protected ?string $suffix = null;

    protected ?string $placeholder = null;

    protected bool $isReadOnly = false;

    protected bool $isDisabled = false;

    /**
     * Define o valor padrão do campo
     */
    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    /**
     * Define o campo como somente leitura (readonly)
     */
    public function readonly(bool $condition = true): static
    {
        $this->isReadOnly = $condition;

        return $this;
    }

    /**
     * Define o campo como desabilitado (disabled)
     */
    public function disabled(bool $condition = true): static
    {
        $this->isDisabled = $condition;

        return $this;
    }

    /**
     * Define o texto de ajuda (exibido abaixo do campo)
     */
    public function helpText(string|Closure $text): static
    {
        $this->helpText = $text;

        return $this;
    }

    /**
     * Define o texto de ajuda (exibido abaixo do campo)
     */
    public function helperText(string|Closure $text): static
    {
        $this->helpText = $text;

        return $this;
    }

    /**
     * Define uma dica ou actions (exibidas abaixo do campo)
     * Pode ser:
     * - String simples: texto de ajuda
     * - Array: lista de actions a serem renderizadas
     * - Closure: função que retorna string ou array de actions
     *
     * @param  string|array|Closure|AbstractColumn  $hint  Texto ou array de actions
     */
    public function hint(string|array|Closure|AbstractColumn $hint): static
    {
        $this->hint[] = $hint;

        return $this;
    }

    /**     * Define actions como hint (alias mais semântico para hint com array)
     *
     * @param  array|Closure  $actions  Array de actions ou Closure que retorna array
     */
    public function hintActions(array|Closure $actions): static
    {
        $this->hint = $actions;

        return $this;
    }

    /**     * Adiciona conteúdo/ação antes do campo
     * Pode ser um texto, ícone ou action
     */
    public function prepend(string|array|Closure $content): static
    {
        $this->prepend = $content;

        return $this;
    }

    /**
     * Adiciona conteúdo/ação depois do campo
     * Pode ser um texto, ícone ou action
     */
    public function append(string|array|Closure $content): static
    {
        $this->append = $content;

        return $this;
    }

    /**
     * Adiciona um prefixo ao campo (ex: R$, +55)
     */
    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Adiciona um sufixo ao campo (ex: kg, m², %)
     */
    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Define o placeholder do campo
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Retorna o valor padrão
     */
    public function getDefault(): mixed
    {
        return $this->evaluate($this->default);
    }

    /**
     * Retorna o texto de ajuda
     */
    public function getHelpText(): ?string
    {
        return $this->evaluate($this->helpText);
    }

    /**
     * Retorna a dica (pode ser string ou array de actions)
     */
    public function getHint(): string|array|AbstractColumn|null
    {
        $hints = [];
        if (is_array($this->hint)) {
            foreach ($this->hint as $hint) {
                if ($hint instanceof AbstractColumn) {
                    $hints[] = $hint->toArray($this->getModel());
                } else {
                    $hints[] = $this->evaluate($hint);
                }
            }

            return $hints;
        }

        return $this->evaluate($this->hint);
    }

    /**
     * Retorna o conteúdo prepend
     */
    public function getPrepend(): string|array|null
    {
        return $this->evaluate($this->prepend);
    }

    /**
     * Retorna o conteúdo append
     */
    public function getAppend(): string|array|null
    {
        return $this->evaluate($this->append);
    }

    /**
     * Retorna o prefixo
     */
    public function getPrefix(): ?string
    {
        return $this->evaluate($this->prefix);
    }

    /**
     * Retorna o sufixo
     */
    public function getSuffix(): ?string
    {
        return $this->evaluate($this->suffix);
    }

    /**
     * Retorna o placeholder
     */
    public function getPlaceholder(): ?string
    {
        return $this->evaluate($this->placeholder);
    }

    /**
     * Verifica se o campo é somente leitura
     */
    public function isReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    /**
     * Retorna se o campo é readonly (alias para isReadOnly)
     */
    public function getReadOnly(): bool
    {
        return $this->isReadOnly();
    }

    /**
     * Verifica se o campo está desabilitado
     */
    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }

    /**
     * Retorna se o campo está disabled (alias para isDisabled)
     */
    public function getDisabled(): bool
    {
        return $this->isDisabled();
    }
}
