<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\AbstractColumn;

trait WithActions
{
    protected array $actions = [];

    public function actions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->action($action);
        }

        return $this;
    }

    public function action(AbstractColumn $action): static
    {
        $this->actions[] = $action;

        return $this;
    }

    /**
     * @return array<AbstractColumn>
     */
    public function getArrayActions(): array
    {
        return array_map(fn (AbstractColumn $action) => $action->toArray(), $this->actions);
    }

    public function getActions(): array
    {
        return $this->actions;
    }
}
