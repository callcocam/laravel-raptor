<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongsToFields;
use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

/**
 * SectionField - Campo de seção para agrupamento de campos relacionados
 *
 * @example
 * SectionField::make('settings')
 *     ->label('Configurações (JSON)')
 *     ->fields([
 *         TextField::make('key')
 *             ->label('Chave')
 *             ->required()
 *             ->columnSpanFull(),
 *         TextField::make('value')
 *             ->label('Valor')
 *             ->required()
 *             ->columnSpanFull(),
 *     ])
 */
class SectionField extends Column
{
    use BelongsToFields;

    protected bool $collapsible = false;

    protected bool $defaultOpen = false;

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-section');
        $this->setUp();

    }

    /**
     * Define se a seção é colapsável (accordion)
     */
    public function collapsible(bool $collapsible = true): self
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    /**
     * Define se a seção inicia aberta
     */
    public function defaultOpen(bool $defaultOpen = true): self
    {
        $this->defaultOpen = $defaultOpen;

        return $this;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function isDefaultOpen(): bool
    {
        return $this->defaultOpen;
    }

    public function toArray($model = null): array
    {
        $baseArray = parent::toArray($model);
        // Converte cada field para array
        $fieldsArray = array_map(function ($field) use ($model) {
            return $field->toArray($model);
        }, $this->getFields());

        // Adiciona o mapeamento de campos ao array
        $baseArray['fields'] = $fieldsArray;
        $baseArray['collapsible'] = $this->isCollapsible();
        $baseArray['defaultOpen'] = $this->isDefaultOpen();

        return $baseArray;
    }
}
