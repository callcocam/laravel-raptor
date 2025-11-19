<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns\ManagesCollection;

trait WithBulkActions
{
    use ManagesCollection;

    public function bulkActions(array $actions): static
    {
        return $this->addManyToCollection($actions, 'bulkActions');
    }

    public function bulkAction(AbstractColumn $action): static
    {
        return $this->addToCollection($action, 'bulkActions');
    }

    /**
     * @return array<AbstractColumn>
     */
    public function getArrayBulkActions(): array
    {
        return $this->getCollectionAsArray('bulkActions');
    }

    public function getBulkActions(): array
    {
        return $this->getCollection('bulkActions');
    }

    public function hasBulkActions(): bool
    {
        return $this->hasCollectionItems('bulkActions');
    }
}
