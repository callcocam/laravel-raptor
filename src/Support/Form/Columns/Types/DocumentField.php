<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

class DocumentField extends Column
{
    protected array $fieldMapping = [];

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);

        $this->valueUsing(function ($model, $data = []) {

            $value = data_get($data, $this->getName());

            return [
                $this->getName() => preg_replace('/\D/', '', $value),
            ];
        });

        $this->setUp();
    }

    public function documentType(string $type): self
    {
        $this->component($type);

        return $this;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function cpf(): self
    {
        $this->component('form-field-cpf');

        return $this;
    }

    public function cnpj(): self
    {
        $this->component('form-field-cnpj');

        return $this;
    }

    public function fieldMapping(array $mapping): self
    {
        $this->fieldMapping = $mapping;

        return $this;
    }

    public function getFieldMapping(): array
    {
        return $this->fieldMapping;
    }

    public function toArray($model = null): array
    {
        $baseArray = parent::toArray($model);
        $baseArray['fieldMapping'] = $this->getFieldMapping();

        return $baseArray;
    }
}
