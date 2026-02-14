<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class CheckboxField extends Column
{
    protected bool $isRequired = false;

    protected ?string $description = null;

    protected bool|array $defaultValue = false;

    protected string $layout = 'horizontal';

    protected bool $inline = false;

    protected int $columns = 1;

    protected bool $searchable = true;

    protected bool $showSelectAll = true;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-checkbox');
        $this->setUp();
    }

    /**
     * Define a descrição do campo
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Define o layout dos checkboxes
     *
     * @param  string  $layout  'horizontal' (padrão) ou 'vertical'
     */
    public function layout(string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Define se os checkboxes devem ser exibidos inline (lado a lado)
     * Útil para checkbox groups
     */
    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * Define o número de colunas para o layout do checkbox group
     * Funciona com multiple checkboxes
     *
     * @param  int  $columns  Número de colunas (1-4)
     */
    public function columns(int $columns): self
    {
        $this->columns = max(1, min(4, $columns));

        return $this;
    }

    /**
     * Atalho para criar um checkbox group vertical
     */
    public function stacked(): self
    {
        $this->layout = 'vertical';
        $this->inline = false;

        return $this;
    }

    /**
     * Habilita/desabilita o campo de busca
     * Por padrão está habilitado para grupos com mais de 5 opções
     */
    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Habilita/desabilita o botão "Selecionar todos"
     * Por padrão está habilitado para grupos com mais de 3 opções
     */
    public function showSelectAll(bool $showSelectAll = true): self
    {
        $this->showSelectAll = $showSelectAll;

        return $this;
    }

    public function toArray($model = null): array
    {

        $attributes = parent::toArray($model);
        $options = [];
        // Usa componente diferente se for checkbox group
        if ($this->isMultiple()) {
            $this->component('form-field-checkbox-group');

            $options = $this->getOptions($model);
        }

        return array_merge($attributes, [
            'required' => $this->isRequired,
            'description' => $this->description,
            'default' => $this->defaultValue,
            'options' => $options,
            'multiple' => $this->isMultiple(),
            'layout' => $this->layout,
            'inline' => $this->inline,
            'columns' => $this->columns,
            'isGroup' => $this->isMultiple(),
            'searchable' => $this->searchable,
            'showSelectAll' => $this->showSelectAll,
            'component' => $this->getComponent(),
        ]);
    }
}
