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
                   $columnName = $column->getName();
                   
                   // BelongsToMany - Muitos para muitos
                   if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                       if (isset($data[$columnName])) {
                           $model->$relationship()->sync($data[$columnName]);
                       }
                   } 
                   
                   // HasMany - Um para muitos
                   elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                       if (isset($data[$columnName]) && is_array($data[$columnName])) {
                           $this->syncHasManyRelation($model, $relationship, $data[$columnName]);
                       }
                   }
                   
                   // HasOne - Um para um
                   elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                       if (isset($data[$columnName])) {
                           $this->updateHasOneRelation($model, $relationship, $data[$columnName]);
                       }
                   }
                   
                   // MorphMany - Polimórfico um para muitos
                   elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphMany) {
                       if (isset($data[$columnName]) && is_array($data[$columnName])) {
                           $this->syncMorphManyRelation($model, $relationship, $data[$columnName]);
                       }
                   }
                   
                   // MorphOne - Polimórfico um para um
                   elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphOne) {
                       if (isset($data[$columnName])) {
                           $this->updateMorphOneRelation($model, $relationship, $data[$columnName]);
                       }
                   }
                   
                   // MorphToMany - Polimórfico muitos para muitos
                   elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphToMany) {
                       if (isset($data[$columnName])) {
                           $model->$relationship()->sync($data[$columnName]);
                       }
                   }
               }
           }
        }
    }

    /**
     * Sincroniza relacionamento HasMany
     * Remove itens não presentes, atualiza existentes e cria novos
     */
    protected function syncHasManyRelation($model, string $relationship, array $items): void
    {
        $existingIds = [];
        
        foreach ($items as $itemData) {
            // Remove timestamps se vieram do frontend (Laravel gerencia automaticamente)
            unset($itemData['created_at'], $itemData['updated_at']);
            
            if (isset($itemData['id']) && $itemData['id']) {
                // Atualiza existente
                $model->$relationship()->where('id', $itemData['id'])->update($itemData);
                $existingIds[] = $itemData['id'];
            } else {
                // Remove id se vier vazio
                unset($itemData['id']);
                // Cria novo
                $created = $model->$relationship()->create($itemData);
                $existingIds[] = $created->id;
            }
        }
        
        // Remove itens que não estão mais presentes
        if (!empty($existingIds)) {
            $model->$relationship()->whereNotIn('id', $existingIds)->delete();
        } else {
            $model->$relationship()->delete();
        }
    }

    /**
     * Atualiza relacionamento HasOne
     */
    protected function updateHasOneRelation($model, string $relationship, $itemData): void
    {
        if (is_array($itemData)) {
            // Remove timestamps se vieram do frontend
            unset($itemData['created_at'], $itemData['updated_at']);
            
            $related = $model->$relationship()->first();
            
            if ($related) {
                // Atualiza existente
                $related->update($itemData);
            } else {
                // Remove id se vier vazio
                unset($itemData['id']);
                // Cria novo
                $model->$relationship()->create($itemData);
            }
        }
    }

    /**
     * Sincroniza relacionamento MorphMany (polimórfico um para muitos)
     * Ex: User morphMany Address
     */
    protected function syncMorphManyRelation($model, string $relationship, array $items): void
    {
        $existingIds = [];
        
        foreach ($items as $itemData) {
            // Remove timestamps se vieram do frontend (Laravel gerencia automaticamente)
            unset($itemData['created_at'], $itemData['updated_at']);
            
            if (isset($itemData['id']) && $itemData['id']) {
                // Atualiza existente
                $model->$relationship()->where('id', $itemData['id'])->update($itemData);
                $existingIds[] = $itemData['id'];
            } else {
                // Remove id se vier vazio
                unset($itemData['id']);
                // Cria novo (morphMany adiciona automaticamente morphType e morphId)
                $created = $model->$relationship()->create($itemData);
                $existingIds[] = $created->id;
            }
        }
        
        // Remove itens que não estão mais presentes
        if (!empty($existingIds)) {
            $model->$relationship()->whereNotIn('id', $existingIds)->delete();
        } else {
            // Se não há IDs, remove todos
            $model->$relationship()->delete();
        }
    }

    /**
     * Atualiza relacionamento MorphOne (polimórfico um para um)
     */
    protected function updateMorphOneRelation($model, string $relationship, $itemData): void
    {
        if (is_array($itemData)) {
            // Remove timestamps se vieram do frontend
            unset($itemData['created_at'], $itemData['updated_at']);
            
            $related = $model->$relationship()->first();
            
            if ($related) {
                // Atualiza existente
                $related->update($itemData);
            } else {
                // Remove id se vier vazio
                unset($itemData['id']);
                // Cria novo (morphOne adiciona automaticamente morphType e morphId)
                $model->$relationship()->create($itemData);
            }
        }
    }
}
