<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Info\Columns\Types;

use Callcocam\LaravelRaptor\Support\Info\Column;

class HasManyColumn extends Column
{
    protected string $type = 'has-many';

    protected ?string $component = 'info-column-has-many'; 

    protected ?string $displayField = 'name';

    protected int $limit = 5;

    protected array $actions = [];

    public function __construct($name, $label = null)
    {
        parent::__construct($name, $label);

        $this->icon('List');
    }

    

    /**
     * Define qual campo será exibido dos itens relacionados
     */
    public function displayField(string $field): self
    {
        $this->displayField = $field;

        return $this;
    }

    /**
     * Define o limite de itens a serem exibidos
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Define as ações disponíveis para cada item
     */
    public function actions(array $actions): self
    {
        $this->actions = $actions;

        return $this;
    }

    public function render(mixed $value, $row = null): array
    {
        $items = []; 
       
        
        // Se value é uma coleção ou array
        if (is_iterable($value)) {
            foreach ($value as $item) {
                if (is_object($item)) {
                    // Processa as ações para cada item individualmente
                    $itemActions = [];
                    foreach ($this->actions as $action) { 
                        if (method_exists($action, 'toArray')) { 
                            $actionArray = $action->render($item); 
                            
                            $itemActions[] = $actionArray;
                        }
                    }
                    
                    $items[] = [
                        'id' => $item->id ?? null,
                        'display' => $item->{$this->displayField} ?? (string) $item,
                        'data' => $item,
                        'actions' => $itemActions,
                    ];
                } else {
                    $items[] = [
                        'id' => null,
                        'display' => (string) $item,
                        'data' => $item,
                        'actions' => [],
                    ];
                }
            }
        }

        // Limita os itens
        $hasMore = count($items) > $this->limit;
        $items = array_slice($items, 0, $this->limit);

        return [
            'items' => $items,
            'hasMore' => $hasMore,
            'total' => is_countable($value) ? count($value) : 0,
            'icon' => $this->getIcon(),
            'tooltip' => $this->getTooltip(),
            'type' => $this->getType(),
            'component' => $this->getComponent(),
            'displayField' => $this->displayField,
        ];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'relationship' => $this->relationship,
            'displayField' => $this->displayField,
            'limit' => $this->limit,
        ]);
    }
}
