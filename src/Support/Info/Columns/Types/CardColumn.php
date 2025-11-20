<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class CardColumn extends Column
{
    protected string $type = 'card';

    protected ?string $component = 'info-column-card';

    protected array $columns = [];

    protected ?string $title = null;

    protected ?string $description = null;

    protected bool $collapsible = false;

    protected bool $defaultExpanded = true;

    /**
     * Define o título do card
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Define a descrição do card
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Define as colunas do card
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Define se o card pode ser colapsado
     */
    public function collapsible(bool $collapsible = true, bool $defaultExpanded = true): self
    {
        $this->collapsible = $collapsible;
        $this->defaultExpanded = $defaultExpanded;

        return $this;
    }

    /**
     * Renderiza o card com suas colunas
     *
     * @param  mixed  $value  - Este é ignorado para CardColumn pois ele usa dados globais
     */
    public function render($value, $row = null): array
    {
        // CardColumn não usa $value diretamente, será populado depois
        return array_merge([
            'text' => $this->title ?? $this->getLabel(),
            'icon' => $this->getIcon(),
            'tooltip' => $this->getTooltip(),
            'type' => $this->getType(),
            'component' => $this->getComponent(),
            'columns' => [], // Será populado pelo InfoList
            'title' => $this->title,
            'description' => $this->description,
            'collapsible' => $this->collapsible,
            'defaultExpanded' => $this->defaultExpanded,
            '_columns_to_render' => $this->columns, // Metadata para o InfoList
        ], $this->getGridLayoutConfig());
    }
}
