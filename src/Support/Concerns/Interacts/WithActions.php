<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns\ManagesCollection;

trait WithActions
{
    use ManagesCollection;

    public function actions(array $actions): static
    {
        return $this->addManyToCollection($actions, 'actions');
    }

    public function action(AbstractColumn $action): static
    {
        return $this->addToCollection($action, 'actions');
    }

    /**
     * @return array<AbstractColumn>
     */
    public function getArrayActions(): array
    {
        return $this->getCollectionAsArray('actions');
    }

    public function getActions(): array
    {
        return $this->getCollection('actions');
    }
}
