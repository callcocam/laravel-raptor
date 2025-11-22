<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns\ManagesCollection;

trait WithColumns
{
    use ManagesCollection;

    public function columns(array $columns): static
    {
        return $this->addManyToCollection($columns, 'columns');
    }

    public function column(AbstractColumn $column): static
    {
        return $this->addToCollection($column, 'columns');
    }

    public function getColumns(): array
    {
        return $this->getCollection('columns');
    }

    /**
     * @return array<AbstractColumn>
     */
    public function getArrayColumns($model = null): array
    {
        // Usa transformer customizado para lidar com lógica de searchable
        return $this->getCollectionAsArray('columns', function (AbstractColumn $column) use ($model) {
            if (method_exists($column, 'isSearchable')) {
                if ($column->isSearchable()) {
                    if (method_exists($this, 'setSearches')) {
                        $this->setSearches($column->getName());
                    }
                }
            }
            return $column->toArray($model);
        });
    }
}
