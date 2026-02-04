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
 * BuscaCepField - Campo de busca de CEP com preenchimento automático
 * 
 * Integra com API ViaCEP para buscar endereço automaticamente
 * 
 * @example
 * BuscaCepField::make('zip_code')
 *     ->label('Endereço')
 *     ->fieldMapping([
 *         'logradouro' => 'street',
 *         'bairro' => 'neighborhood',
 *         'localidade' => 'city',
 *         'uf' => 'state',
 *         'complemento' => 'complement',
 *     ])
 *     ->fields([
 *         TextField::make('street')->label('Rua')->required()->columnSpan(8),
 *         TextField::make('number')->label('Número')->required()->columnSpan(4),
 *         TextField::make('complement')->label('Complemento')->columnSpan(6),
 *         TextField::make('neighborhood')->label('Bairro')->required()->columnSpan(6),
 *         TextField::make('city')->label('Cidade')->required()->readonly()->columnSpan(8),
 *         TextField::make('state')->label('UF')->required()->readonly()->columnSpan(4),
 *     ])
 */
class BuscaCepField extends Column
{
    use BelongsToFields;

    protected array $fieldMapping = [];

    protected  string $exuteOnChange = 'zip_code';

    protected array $requiredFields = [];

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label);
        $this->component('form-field-busca-cep');
        $this->setUp();
        $this->defaultUsing(function ($model, $data=[]) {
            return data_get($model, $this->name, null);
        });
    }

    protected function setUp(): void
    {
        // Mapeamento padrão da API ViaCEP
        $this->fieldMapping = [
            'cep' => 'zip_code',
            'logradouro' => 'street',
            'bairro' => 'district',
            'localidade' => 'city',
            'uf' => 'state',
            'complemento' => 'complement',
        ];

        // Campos padrão
        $this->fields([
            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('zip_code')
                ->label('CEP')
                ->required($this->isRequired())
                ->placeholder('Digite o CEP')
                ->columnSpan(4),
            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('street')
                ->label('Rua')
                ->required($this->isRequired())
                ->placeholder('Rua, avenida, etc.')
                ->columnSpan(6),
            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('number')
                ->label('Número')
                ->required($this->isRequired())
                ->placeholder('Nº')
                ->columnSpan(2),
            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('complement')
                ->label('Complemento')
                ->placeholder('Apto, bloco, etc.')
                ->columnSpan(6),
            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('district')
                ->label('Bairro')
                ->required($this->isRequired())
                ->placeholder('Bairro')
                ->columnSpan(6),
            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('city')
                ->label('Cidade')
                ->required($this->isRequired())
                ->readonly()
                ->placeholder('Cidade')
                ->columnSpan(8),
            \Callcocam\LaravelRaptor\Support\Form\Columns\Types\TextField::make('state')
                ->label('UF')
                ->required($this->isRequired())
                ->readonly()
                ->placeholder('UF')
                ->columnSpan(4),
        ]);
    }

    /**
     * Verifica se o campo é obrigatório
     */
    public function isRequired(): bool
    {
        return in_array($this->name, $this->requiredFields);
    }

    public function executeOnChange(string $fieldName): static
    {
        $this->exuteOnChange = $fieldName;
        return $this;
    }

    public function getExecuteOnChange(): string
    {
        return $this->exuteOnChange;
    }

    /**
     * Define o mapeamento dos campos da API para os campos do formulário
     * 
     * @param array $mapping Mapeamento ['campo_api' => 'campo_formulario']
     * @return static
     * 
     * @example
     * ->fieldMapping([
     *     'logradouro' => 'street',
     *     'bairro' => 'neighborhood',
     *     'localidade' => 'city',
     *     'uf' => 'state',
     * ])
     */
    public function fieldMapping(array $mapping): static
    {
        $this->fieldMapping = array_merge($this->fieldMapping, $mapping);

        return $this;
    }

    /**
     * Retorna o mapeamento de campos
     * 
     * @return array
     */
    public function getFieldMapping(): array
    {
        return $this->fieldMapping;
    }

    public function toArray($model = null): array
    {
        $baseArray = parent::toArray($model);
        // Converte cada field para array
        $fieldsArray = array_map(function ($field) use ($model) {
            return $field->toArray($model);
        }, $this->getFields());
        // Adiciona o mapeamento de campos ao array
        $baseArray['fieldMapping'] = $this->getFieldMapping();
        $baseArray['fields'] = $fieldsArray;
        $baseArray['executeOnChange'] = $this->getExecuteOnChange();

        return $baseArray;
    }
}
