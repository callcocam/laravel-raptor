<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\AbstractColumn;

trait InteractWithBulkActions
{
    protected array $bulkActions = [];

    public function bulkActions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->bulkAction($action);
        }

        return $this;
    }

    public function bulkAction(AbstractColumn $action): static
    {
        $this->bulkActions[] = $action;

        return $this;
    }

    /**
     * @return array<AbstractColumn>
     */
    public function getArrayBulkActions(): array
    {
        return array_map(function (AbstractColumn $action) {
            return $action->toArray();
        }, $this->bulkActions);
    }

    public function getBulkActions(): array
    {
        return $this->bulkActions;
    }

    public function hasBulkActions(): bool
    {
        return ! empty($this->bulkActions);
    }
}
