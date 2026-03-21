<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns\Interacts;

use Callcocam\LaravelRaptor\Support\Concerns\ManagesCollection;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongToRequest;
use Callcocam\LaravelRaptor\Support\Tabs\Tab;

trait WithTabs
{
    use ManagesCollection;
    use BelongToRequest;

    /**
     * Adiciona múltiplas tabs — aceita Tab[] ou arrays crus ['key'=>..., 'name'=>..., 'href'=>...].
     */
    public function tabs(array $tabs): static
    {
        return $this->addManyToCollection($tabs, 'tabs');
    }

    public function tab(Tab $tab): static
    {
        return $this->addToCollection($tab, 'tabs');
    }

    public function getTabs(): array
    {
        return $this->getCollection('tabs');
    }

    /**
     * Serializa as tabs para array — chama toArray() em instâncias Tab ou faz passthrough de arrays crus.
     */
    public function getArrayTabs(): ?array
    {
        $tabs = $this->getTabs();

        if (empty($tabs)) {
            return null;
        }

        return array_values(array_map(
            fn ($tab) => $tab instanceof Tab ? $tab->toArray() : (array) $tab,
            $tabs
        ));
    }
}
