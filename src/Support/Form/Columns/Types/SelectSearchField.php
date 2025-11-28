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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SelectAutoCompleteField - Campo de seleção com auto-preenchimento
 *
 * Estende SelectField adicionando capacidade de preencher automaticamente
 * outros campos quando uma opção é selecionada.
 *
 * @example
 * SelectAutoCompleteField::make('product_id')
 *     ->label('Produto')
 *     ->options(Product::get(['id', 'name', 'price'])->toArray())
 *     ->autoCompleteValue('id')
 *     ->autoCompleteLabel('name')
 *     ->complete('price', 'unit_price')
 *     ->required()
 */
class SelectSearchField extends Column
{
    use HasAutoComplete;

    protected bool $isRequired = false;

    protected ?string $placeholder = null;

    protected bool $searchable = true;

    protected Closure|string|null $dependsOn = null;

    protected ?Builder $baseQuery = null;

    protected int $limit = 100;

    protected array $searchableFields = ['id', 'name'];

    protected array $selectFields = ['id', 'name'];

    protected array $where = [];

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        // $this->component('form-field-search-select');
        $this->component('form-field-search-combobox');
        $this->setUp(); 
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function dependsOn(Closure|string|null $dependsOn): self
    {
        $this->dependsOn = $dependsOn;

        return $this;
    }

    public function getDependsOn(): Closure|string|null
    {
        return $this->evaluate($this->dependsOn);
    }
    /**
     * Define a query para busca dinâmica
     *
     * @param Builder|Closure|string|Model $query Classe do model ou instância
     * @param string|null $label Coluna para exibir (padrão: 'name')
     * @param string|null $value Coluna para valor (padrão: 'id')
     * @param array|null $select Colunas para seleção (padrão: ['id', 'name'])
     * @return self
     */
    public function query(Builder|Closure|string|Model $query, ?string $label = null, ?string $value = null, ?array $select = ['id', 'name']): self
    {
        return $this->baseQuery($query, $label, $value, $select)->searchable(true)->required(true);
    }

    /**
     * Define a query para busca dinâmica
     *
     * @param Builder|Closure|string|Model $query Classe do model ou instância
     * @param string|null $label Coluna para exibir (padrão: 'name')
     * @param string|null $value Coluna para valor (padrão: 'id')
     * @param array|null $select Colunas para seleção (padrão: ['id', 'name'])
     * @return self
     */
    public function baseQuery(Builder|Closure|string|Model $query, ?string $label = null, ?string $value = null, ?array $select = ['id', 'name']): self
    {
        if ($query instanceof Builder) {
            $this->baseQuery = $query;
        } elseif ($query instanceof Closure) {
            $this->baseQuery = $this->evaluate($query);
        } elseif ($query instanceof Model) {
            $this->baseQuery = $query->newQuery()->select($select);
        } elseif (is_string($query)) {
            $this->baseQuery = (new $query())->newQuery()->select($select);
        }

        if ($select) {
            $this->selectFields($select);
        }
        if ($label) {
            $this->autoCompleteLabel($label);
        }
        if ($value) {
            $this->autoCompleteValue($value);
        }
        return $this;
    }

    public function getBaseQuery(): ?Builder
    {
        if ($this->hasRelationship()) {
            return $this->processRelationshipOptions()->newQuery();
        }
        return $this->baseQuery;
    }

    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }

    public function searchableFields(array $fields): self
    {
        $this->searchableFields = $fields;

        return $this;
    }

    public function selectFields(array $fields): self
    {
        $this->selectFields = $fields;

        return $this;
    }

    public function getSelectFields(): array
    {
        return $this->selectFields;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function where(array $where): self
    {
        $this->where = $where;

        return $this;
    }
    public function getWhere(): array
    {
        return $this->where;
    }

    protected function processOptionsForQuery(): array
    {
        $query = $this->getBaseQuery();
        if ($query) {
            return $query
                ->when($this->getWhere(), function ($query) {
                    $query->whereIn($this->getOptionKey(), $this->getWhere());
                })
                ->when(request()->has($this->getName()), function ($query) { 
                    Log::info('index', [$this->getIndex(), $this->getName()]);
                    $searchableFields = implode(', ', $this->getSearchableFields()); 
                    $search = request()->input($this->getName());
                    $where = "CONCAT({$searchableFields}) LIKE '%" . $search . "%'"; 
                    $query->orWhereRaw($where);
                })
                ->select($this->getSelectFields())
                ->limit($this->limit)->get()->toArray();
        }
        return [];
    }

    public function getOptions(): array
    {
        $options = $this->evaluate($this->options);

        return $this->normalizeOptions($options);
    }

    public function toArray($model = null): array
    {
        $this->record($model);

        $optionsData = (object) [];
        $collection = data_get($model, $this->getRelationshipName(), []);
        $baseSearchableFields = [];
        if ($collection instanceof Collection) {
            $baseSearchableFields = $collection->pluck($this->getName())->toArray();
        } elseif ($collection instanceof Model) {
            $baseSearchableFields[] = data_get($collection, $this->getOptionKey());
        }
        if ($baseSearchableFields) {
            $this->where($baseSearchableFields);
        }

        $this->options($this->processOptionsForQuery());

        // Processa as opções BRUTAS antes da normalização
        $optionKey = $this->getOptionKey();
        $optionLabel = $this->getOptionLabel();
        if (! empty($this->autoCompleteFields) || $optionKey || $optionLabel) {
            // Pega as opções brutas (antes de normalizar) 
            $processed = $this->processOptionsForAutoComplete($this->getRawOptions());
            $optionsData = $processed['optionsData'];
        }

        $baseArray = array_merge(parent::toArray($model), [
            'searchable' => $this->searchable,
            'multiple' => $this->isMultiple(),
            'options' => $this->getOptions(),
            'dependsOn' => $this->getDependsOn(),
        ]);
        $baseArray['optionsData'] = $optionsData;

        return array_merge($baseArray, $this->autoCompleteToArray());
    }
}
