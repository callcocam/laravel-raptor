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
    public function getValidationRules($record = null, $request = null): array
    {
        $rules = [];

        foreach ($this->getColumns() as $column) {
            $columnRules = $column->getRules($record);

            if (!empty($columnRules)) {
                if (!in_array($column->getType(), ['password'])) {
                    $rules[$column->getName()] =  $columnRules;
                } else {
                    if ($request && $request->filled($column->getName())) {
                        // Se for um novo registro ou o campo de senha está vazio, aplica as regras
                        $rules[$column->getName()] =  $columnRules;
                    }
                }
            } else {
                if (!in_array($column->getType(), ['password']))
                    $rules[$column->getName()] =  ['nullable'];
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

    /**
     * Atualiza dados relacionados após salvar o modelo principal
     */
    public function updateRelatedData(array $data, $model, $request): void
    { 
        foreach ($this->getColumns() as $column) {
           if ($column->hasRelationship()) {
               $relationship = $column->getRelationship();
               if (method_exists($model, $relationship)) {
                   $relationInstance = $model->$relationship();
                   // Verifica o tipo de relacionamento e atualiza conforme necessário
                   if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                       // Muitos para muitos
                       if (isset($data[$column->getName()])) {
                           $model->$relationship()->sync($data[$column->getName()]);
                       }
                   } elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                       // Um para muitos
                       // Implementar lógica de atualização conforme necessário
                   }elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                       // Um para um
                       // Implementar lógica de atualização conforme necessário
                   } 
                   // Adicionar outros tipos de relacionamento conforme necessário
               }
           }
        }
    }
}
