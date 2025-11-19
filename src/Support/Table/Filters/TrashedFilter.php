<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */
namespace Callcocam\LaravelRaptor\Support\Table\Filters;

use Callcocam\LaravelRaptor\Support\Table\FilterBuilder;

class TrashedFilter extends FilterBuilder
{
    protected string $component = 'filter-trashed';

    protected array $options = [];

    public function __construct(string $name = 'trashed', ?string $label = null)
    {
        parent::__construct($name, $label ?? 'Lixeira');

        $this->options = [
            ['value' => '', 'label' => 'Sem Lixeira'],
            ['value' => 'with', 'label' => 'Com Lixeira'],
            ['value' => 'only', 'label' => 'Somente Lixeira'],
        ];
    }

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    protected function setUp(): void
    {
        $this->queryUsing(function ($query, $value) {
            if ($value === 'with') {
                $query->withTrashed();

                return;
            }

            if ($value === 'only') {
                $query->onlyTrashed();
            }
        });
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options' => $this->getOptions(),
        ]);
    }
}
