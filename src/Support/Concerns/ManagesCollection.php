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
        if ($item === null) {
            return $this;
        }

        if (! isset($this->collections[$key])) {
            $this->collections[$key] = [];
        }

        $this->collections[$key][] = $item;

        return $this;
    }

    /**
     * Retorna todos os itens da coleção
     */
    protected function getCollection(string $key, $model = null): array
    {
        $items = $this->collections[$key] ?? [];

        return array_map(fn ($item) => $this->evaluate($item, [
            'model' => $model,
            'request' => $this->getRequest(),
        ]), $items);
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
     * Retorna coleção renderizada e filtrada por visibilidade
     *
     * @param  string  $key  Chave da coleção
     * @param  mixed  $model  Modelo para passar ao render()
     * @param  mixed  $request  Request para passar ao render()
     * @return array Array de itens renderizados e visíveis
     */
    protected function getCollectionRendered(string $key, $model = null, $request = null): array
    {
        $items = $this->getCollection($key);
        $rendered = [];

        // Se request não foi passado, tenta obter do contexto
        if ($request === null && method_exists($this, 'getRequest')) {
            $request = $this->getRequest();
        }

        foreach ($items as $item) {
            // Se o item tem método render(), usa ele (Actions, etc)
            if (method_exists($item, 'render')) {
                $result = $item->render($model, $request);

                // Filtra apenas itens visíveis
                if ($result !== null && ($result['visible'] ?? true)) {
                    $rendered[] = $result;
                }
            }
            // Senão, usa toArray() ou retorna o item direto
            elseif (method_exists($item, 'toArray')) {
                $rendered[] = $item->toArray();
            } else {
                $rendered[] = $item;
            }
        }

        return $rendered;
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
