<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Concerns;

use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithColumns;

trait InteractWithForm
{ 

    use WithColumns;
    
    /**
     * Retorna o formulário como estrutura de dados
     */
    public function getForm(): ?array
    {
        if (empty($this->getColumns())) {
            return [];
        }

        return [
            'columns' => $this->getArrayColumns(),
        ];
    }

    /**
     * Extrai as regras de validação de todos os campos
     */
    public function getValidationRules(): array
    {
        $rules = [];
        
        foreach ($this->getColumns() as $column) {
            $columnRules = $column->getRules();
            
            if (!empty($columnRules)) {
                $rules[$column->getName()] = $columnRules;
            }
        }

        return $rules;
    }

    /**
     * Extrai as mensagens de validação customizadas
     */
    public function getValidationMessages(): array
    {
        $messages = [];
        
        foreach ($this->getColumns() as $column) {
            $columnMessages = $column->getMessages();
            
            if (!empty($columnMessages)) {
                foreach ($columnMessages as $rule => $message) {
                    $messages["{$column->getName()}.{$rule}"] = $message;
                }
            }
        }

        return $messages;
    }

    /**
     * Retorna apenas os campos obrigatórios
     */
    public function getRequiredFields(): array
    {
        return array_filter($this->getColumns(), fn($column) => $column->isRequired());
    }
}
