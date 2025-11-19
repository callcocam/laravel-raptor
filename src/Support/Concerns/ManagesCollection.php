<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

/**
 * ManagesCollection - Trait genérico para gerenciar coleções de itens
 *
 * Elimina duplicação de código nas traits WithActions, WithBulkActions,
 * WithFilters, WithHeaderActions e WithColumns.
 *
 * Uso:
 * ```php
 * trait WithActions
 * {
 *     use ManagesCollection;
 *
 *     protected function getCollectionKey(): string
 *     {
 *         return 'actions';
 *     }
 *
 *     public function action($action): static
 *     {
 *         return $this->addToCollection($action);
 *     }
 * }
 * ```
 */
trait ManagesCollection
{
    /**
     * Armazena coleções por chave
     * Formato: ['actions' => [...], 'filters' => [...]]
     */
    protected array $collections = [];

    /**
     * Adiciona múltiplos itens à coleção
     */
    protected function addManyToCollection(array $items, string $key): static
    {
        foreach ($items as $item) {
            $this->addToCollection($item, $key);
        }

        return $this;
    }

    /**
     * Adiciona um item à coleção
     */
    protected function addToCollection(mixed $item, string $key): static
    {
        if (! isset($this->collections[$key])) {
            $this->collections[$key] = [];
        }

        $this->collections[$key][] = $item;

        return $this;
    }

    /**
     * Retorna todos os itens da coleção
     */
    protected function getCollection(string $key): array
    {
        return $this->collections[$key] ?? [];
    }

    /**
     * Retorna coleção convertida em array (chama toArray() em cada item)
     */
    protected function getCollectionAsArray(string $key, ?callable $transformer = null): array
    {
        $items = $this->getCollection($key);

        $defaultTransformer = fn ($item) => method_exists($item, 'toArray')
            ? $item->toArray()
            : $item;

        return array_map($transformer ?? $defaultTransformer, $items);
    }

    /**
     * Verifica se a coleção tem itens
     */
    protected function hasCollectionItems(string $key): bool
    {
        return ! empty($this->collections[$key]);
    }

    /**
     * Conta quantos itens há na coleção
     */
    protected function countCollection(string $key): int
    {
        return count($this->collections[$key] ?? []);
    }

    /**
     * Limpa todos os itens da coleção
     */
    protected function clearCollection(string $key): static
    {
        $this->collections[$key] = [];

        return $this;
    }
}
