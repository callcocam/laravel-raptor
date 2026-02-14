<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\AbstractColumn;
use Callcocam\LaravelRaptor\Support\Concerns\ManagesCollection;

trait WithHeaderActions
{
    use ManagesCollection;

    public function headerActions(array $headerActions): static
    {
        return $this->addManyToCollection($headerActions, 'headerActions');
    }

    public function headerAction(AbstractColumn $action): static
    {
        return $this->addToCollection($action, 'headerActions');
    }

    /**
     * @return array<AbstractColumn>
     */
    public function getArrayHeaderActions(): array
    {
        return $this->getCollectionAsArray('headerActions');
    }

    /**
     * Retorna header actions renderizadas e filtradas por visibilidade
     *
     * @param  mixed  $model  Modelo para verificação de visibilidade
     * @param  mixed  $request  Request para contexto
     * @return array Header actions visíveis renderizadas
     */
    public function getRenderedHeaderActions($model = null, $request = null): array
    {
        return $this->getCollectionRendered('headerActions', $model, $request);
    }

    public function getHeaderActions(): array
    {
        return $this->getCollection('headerActions');
    }
}
