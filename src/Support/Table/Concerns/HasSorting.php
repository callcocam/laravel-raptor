<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Table\Concerns;

use Callcocam\LaravelRaptor\Support\Table\Sorting;

trait HasSorting
{
    /**
     * @var array<Sorting>
     */
    protected array $sortings = [];

    public function addSorting(Sorting|string $sorting): static
    {
        if (is_string($sorting)) {
            $sorting = new Sorting($sorting, 'asc');
        }
        $this->sortings[] = $sorting->context($this);

        return $this;
    }

    /**
     * @return array<Sorting>
     */
    public function getSorting(string $name): ?Sorting
    {
        return $this->getSortingByName($name);
    }

    public function getSortingByName(string $name): ?Sorting
    {
        return array_filter($this->sortings, fn (Sorting|string $sorting) => is_string($sorting) ? $sorting === $name : $sorting->name === $name)[0] ?? null;
    }

    public function hasSorting(string $name): bool
    {
        return $this->getSortingByName($name) !== null;
    }

    public function removeSorting(string $name): static
    {
        $this->sortings = array_filter($this->sortings, fn (Sorting $sorting) => $sorting->name !== $name);

        return $this;
    }

    /**
     * @param  array<string>  $names
     */
    public function hasSortings(array $names): bool
    {
        return count(array_intersect($names, $this->getSortingNames())) === count($names);
    }

    /**
     * @return array<string>
     */
    public function getSortingNames(): array
    {
        return array_map(fn (Sorting $sorting) => $sorting->name, $this->sortings);
    }

    /**
     * @return array<Sorting>
     */
    public function getSortings(): array
    {
        return array_map(fn (Sorting $sorting) => $sorting->toArray(), $this->sortings);
    }
}
