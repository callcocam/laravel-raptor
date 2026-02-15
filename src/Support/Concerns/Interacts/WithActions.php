<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns\ManagesCollection;
use Closure;

trait WithActions
{
    use ManagesCollection;

    public function actions(Closure|array $actions): static
    {
        return $this->addManyToCollection($this->evaluate($actions), 'actions');
    }

    public function action(Closure|AbstractColumn $action): static
    {
        return $this->addToCollection($this->evaluate($action), 'actions');
    }

    /**
     * @return array<AbstractColumn>
     */
    public function getArrayActions(): array
    {
        return $this->getCollectionAsArray('actions');
    }

    /**
     * Retorna actions renderizadas e filtradas por visibilidade
     *
     * @param  mixed  $model  Modelo para verificação de visibilidade
     * @param  mixed  $request  Request para contexto
     * @return array Actions visíveis renderizadas
     */
    public function getRenderedActions($model = null, $request = null): array
    {
        return $this->getCollectionRendered('actions', $model, $request);
    }

    public function getActions($model = null): array
    {
        return $this->getCollection('actions', $model);
    }
}
