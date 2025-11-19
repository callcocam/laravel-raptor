<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Concerns;

use Callcocam\LaravelRaptor\Support\Table\Search;

trait HasSearch
{
    protected array $searches = [];

    protected ?Search $searcheable = null;

    public function searcheable(?Search $searcheable): static
    {
        if (! $searcheable) {
            $searcheable = (new Search($this->name))->context($this);
        }
        $this->searcheable = $searcheable;

        return $this;
    }

    /**
     * @return Search | null
     */
    public function isSearcheable(): Search|null|bool
    {
        if (empty($this->searches)) {
            return false;
        }

        return true;
    }

    public function getSearcheable(): ?Search
    {
        return $this->evaluate($this->searcheable);
    }

    public function getSearch(): ?Search
    {
        return $this->searcheable;
    }

    public function getSearchByName(string $name): ?Search
    {
        return array_filter($this->searches, fn (Search $search) => $search->name === $name)[0] ?? null;
    }

    public function setSearches(string $name): static
    {
        $this->searches[] = (new Search($name))->context($this);

        return $this;
    }

    /**
     * @return array<Search>
     */
    public function getSearches(): array
    {
        return $this->searches;
    }

    public function getSearchByNameOrNull(string $name): ?Search
    {
        return $this->getSearchByName($name) ?? null;
    }
}
