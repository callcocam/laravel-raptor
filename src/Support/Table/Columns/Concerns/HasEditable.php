<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Columns\Concerns;

/**
 * Trait para colunas que podem ser editadas inline na tabela.
 *
 * Quando editable é true, o componente pode ser trocado para a variante
 * em resources/js/components/table/columns/editable/ (ex.: table-column-status-editable).
 */
trait HasEditable
{
    protected bool $editable = false;

    protected ?string $executeUrl = null;

    protected ?string $statusKey = null;

    /** @var array<int, string> */
    protected array $activeValues = ['active', 'published', '1', 'true', 'ativo'];

    public function editable(bool $editable = true): static
    {
        $this->editable = $editable;

        return $this;
    }

    public function isEditable(): bool
    {
        return $this->editable;
    }

    /**
     * URL para executar a atualização (POST) quando o usuário edita inline.
     */
    public function executeUrl(string $url): static
    {
        $this->executeUrl = $url;

        return $this;
    }

    public function getExecuteUrl(): ?string
    {
        return $this->executeUrl;
    }

    /**
     * Nome do campo enviado no payload (ex.: status, is_active).
     * Default: nome da coluna.
     */
    public function statusKey(string $key): static
    {
        $this->statusKey = $key;

        return $this;
    }

    public function getStatusKey(): ?string
    {
        return $this->statusKey ?? $this->getName();
    }

    /**
     * Valores considerados "ativos" para toggle (ex.: status badge verde).
     *
     * @param  array<int, string>  $values
     */
    public function activeValues(array $values): static
    {
        $this->activeValues = $values;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getActiveValues(): array
    {
        return $this->activeValues;
    }

    /**
     * Retorna o componente a ser usado (normal ou variante -editable).
     */
    public function getComponent(): ?string
    {
        $base = $this->component ?? 'table-column-text';

        if ($this->editable) {
            return $base.'-editable';
        }

        return $base;
    }

    /**
     * Array de atributos para merge em toArray() quando a coluna é editável.
     *
     * @return array<string, mixed>
     */
    public function getEditableToArray(): array
    {
        if (! $this->editable) {
            return [];
        }

        return [
            'editable' => true,
            'executeUrl' => $this->getExecuteUrl(),
            'statusKey' => $this->getStatusKey(),
            'activeValues' => $this->getActiveValues(),
        ];
    }
}
