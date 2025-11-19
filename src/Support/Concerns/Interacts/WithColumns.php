<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\AbstractColumn;

trait WithColumns
{
    protected array $columns = [];

    public function columns(array $columns): static
    {
        foreach ($columns as $column) {
            $this->column($column);
        }

        return $this;
    }

    public function column(AbstractColumn $column): static
    {
        $this->columns[] = $column;

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array<AbstractColumn>
     */
    public function getArrayColumns(): array
    {
        return array_map(function (AbstractColumn $column) {
            if (method_exists($column, 'isSearchable')) {
                if ($column->isSearchable()) {
                    if (method_exists($this, 'setSearches')) {
                        $this->setSearches($column->getName());
                    }
                }
            }

            return $column->toArray();
        }, $this->getColumns());
    }
}
