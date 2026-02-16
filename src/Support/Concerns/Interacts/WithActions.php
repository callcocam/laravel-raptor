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
        if ($actions instanceof Closure) {
            return $this->addToCollection($actions, 'actions');
        }

        return $this->addManyToCollection($actions, 'actions');
    }

    public function action(Closure|AbstractColumn $action): static
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
        $items = $this->collections['actions'] ?? [];
        $flat = [];

        foreach ($items as $item) {
            if ($item instanceof Closure) {
                // if ($model !== null) {
                    $resolved = $this->evaluate($item, ['model' => $model, 'request' => $this->getRequest()]);
                    $resolved = is_array($resolved) ? $resolved : [$resolved];
                    $flat = array_merge($flat, array_filter($resolved));
                // }
            } else {
                $resolved = $this->evaluate($item, ['model' => $model, 'request' => $this->getRequest()]);
                if ($resolved !== null) {
                    $flat[] = $resolved;
                }
            }
        }

        return $flat;
    }
}
