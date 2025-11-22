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
    public function getValidationRules($record = null): array
    {
        $rules = [];

        foreach ($this->getColumns() as $column) {
            $columnRules = $column->getRules($record);

            if (!empty($columnRules)) {
                $rules[$column->getName()] =  $columnRules;
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
     * Extrai dados do formulário do request de forma segura
     * 
     * Preserva TODOS os campos do request e apenas aplica customização
     * quando o campo tiver um getValueUsing() definido.
     * 
     * @param \Illuminate\Http\Request $request
     * @param mixed $model Modelo existente (para edição)
     * @return array Dados do formulário
     */
    public function getFormData($data, $model = null): array
    {

        // Aplica customizações apenas nos campos definidos
        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();

            try {
                // Tenta obter valor customizado do campo
                $valueUsing = $column->getValueUsing($data, $model);

                // Se retornou algo válido (não null e não vazio), usa a customização
                if ($valueUsing !== null) {
                    // Verifica se retornou array (campos múltiplos)
                    if (is_array($valueUsing)) {
                        // Merge customização com dados existentes
                        $data = array_merge($data, $valueUsing);
                    } else {
                        // Sobrescreve com valor customizado
                        $data[$columnName] = $valueUsing;
                    }
                }
            } catch (\Throwable $e) {
                // Log error mas mantém valor original do request
                logger()->warning("Error processing form field '{$columnName}': " . $e->getMessage());
                // Não faz nada - mantém o valor original do request
            }
        }

        return $data;
    }
    /**
     * Retorna apenas os campos obrigatórios
     */
    public function getRequiredFields(): array
    {
        return array_filter($this->getColumns(), fn($column) => $column->isRequired());
    }
}
