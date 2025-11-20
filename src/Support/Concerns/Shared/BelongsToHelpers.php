<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Shared;

use Closure;

trait BelongsToHelpers
{
    protected mixed $default = null;

    protected string|Closure|null $helpText = null;

    protected string|Closure|null $hint = null;

    protected string|array|Closure|null $prepend = null;

    protected string|array|Closure|null $append = null;

    protected ?string $prefix = null;

    protected ?string $suffix = null;

    protected ?string $placeholder = null;

    /**
     * Define o valor padrão do campo
     */
    public function default(mixed $value): static
    {
        $this->default = $value;

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
     * Define uma dica (similar ao helpText, mas pode ter estilo diferente)
     */
    public function hint(string|Closure $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    /**
     * Adiciona conteúdo/ação antes do campo
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
     * Retorna a dica
     */
    public function getHint(): ?string
    {
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
     * 
     * @return string|null
     */
    public function getPlaceholder(): ?string
    {
        return $this->evaluate($this->placeholder);
    }
}
