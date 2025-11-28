<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;
use Callcocam\LaravelRaptor\Support\Form\Columns\Concerns\HasAutoComplete;
use Closure;
use Illuminate\Database\Eloquent\Builder; 
use Illuminate\Database\Eloquent\Relations\Relation;

class RelationshipField extends Column
{
    use HasAutoComplete;

    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected bool $searchable = true; 

    protected Closure|string|Builder|null $queryUsing = null;

    protected ?string $titleAttribute = 'name';

    protected ?string $keyAttribute = 'id';

    protected bool $preload = false;

    protected int $searchMinLength = 2;

    protected int $searchDebounce = 300;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-relationship');
        $this->setUp();
    }
 

    /**
     * Retorna o nome do relacionamento
     */
    public function getRelationship(): ?string
    {
        return $this->relationship ?? $this->getName();
    }

    /**
     * Define uma query customizada para buscar os registros
     */
    public function queryUsing(Closure|string|Builder|null $queryUsing): self
    {
        $this->queryUsing = $queryUsing;

        return $this;
    }

    /**
     * Retorna a query configurada
     */
    public function getQueryUsing(): Closure|string|Builder|null
    {
        if (is_string($this->queryUsing)) {
            return app($this->queryUsing);
        }
        if ($this->queryUsing instanceof Builder) {
            return $this->queryUsing;
        }

        return $this->evaluate($this->queryUsing);
    }

    /**
     * Habilita/desabilita busca
     */
    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }
 

    /**
     * Retorna se é múltiplo
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Define o atributo usado como título/label
     */
    public function titleAttribute(string $attribute): self
    {
        $this->titleAttribute = $attribute;

        return $this;
    }

    /**
     * Define o atributo usado como chave/value
     */
    public function keyAttribute(string $attribute): self
    {
        $this->keyAttribute = $attribute;

        return $this;
    }

    /**
     * Define se deve pré-carregar as opções
     */
    public function preload(bool $preload = true): self
    {
        $this->preload = $preload;

        return $this;
    }

    /**
     * Define o tamanho mínimo para busca
     */
    public function searchMinLength(int $length): self
    {
        $this->searchMinLength = $length;

        return $this;
    }

    /**
     * Define o debounce da busca em milissegundos
     */
    public function searchDebounce(int $debounce): self
    {
        $this->searchDebounce = $debounce;

        return $this;
    }

    /**
     * Busca as opções iniciais ou baseado em query
     */
    protected function getInitialOptions($model = null): array
    {
        // Se tem query customizada, usa ela
        if ($this->queryUsing) {
            $query = $this->getQueryUsing();
            if ($query instanceof Builder) {
                return $query->pluck($this->titleAttribute, $this->keyAttribute)->toArray();
            }
        }

        // Se não tem modelo ou relacionamento, retorna vazio
        if (! $model || ! $this->relationship) {
            return [];
        }

        // Tenta buscar pelo relacionamento
        try {
            $relationshipMethod = $this->getRelationship();
            
            if (! method_exists($model, $relationshipMethod)) {
                return [];
            }

            $relation = $model->{$relationshipMethod}();
            
            if (! $relation instanceof Relation) {
                return [];
            }

            // Se for preload, carrega algumas opções
            if ($this->preload) {
                return $relation->getRelated()
                    ->limit(50)
                    ->pluck($this->titleAttribute, $this->keyAttribute)
                    ->toArray();
            }

            // Se já tem valor selecionado, carrega apenas ele
            $selectedValue = data_get($model, $this->getName());
            if ($selectedValue) {
                if (is_array($selectedValue) || $selectedValue instanceof \Illuminate\Support\Collection) {
                    return $relation->getRelated()
                        ->whereIn($this->keyAttribute, $selectedValue)
                        ->pluck($this->titleAttribute, $this->keyAttribute)
                        ->toArray();
                } else {
                    $record = $relation->getRelated()->find($selectedValue);
                    return $record ? [$record->{$this->keyAttribute} => $record->{$this->titleAttribute}] : [];
                }
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function toArray($model = null): array
    {
        $optionsData = (object) [];

        // Processa as opções para autocomplete se necessário
        $optionKey = $this->getOptionKey();
        $optionLabel = $this->getOptionLabel();
        if (! empty($this->autoCompleteFields) || $optionKey || $optionLabel) {
            $processed = $this->processOptionsForAutoComplete($this->getInitialOptions($model));
            $optionsData = $processed['optionsData'];
        }

        $baseArray = array_merge(parent::toArray($model), [
            'searchable' => $this->searchable,
            'multiple' => $this->multiple,
            'relationship' => $this->getRelationship(),
            'titleAttribute' => $this->titleAttribute,
            'keyAttribute' => $this->keyAttribute,
            'preload' => $this->preload,
            'searchMinLength' => $this->searchMinLength,
            'searchDebounce' => $this->searchDebounce,
            'options' => $this->getInitialOptions($model),
        ]);

        $baseArray['optionsData'] = $optionsData;

        return array_merge($baseArray, $this->autoCompleteToArray());
    }
}
