<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Concerns;

use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithColumns;
use Callcocam\LaravelRaptor\Support\Form\Columns\Types\SectionField;

trait InteractWithForm
{
    use WithColumns;

    /**
     * Retorna true se a coluna é um SectionField flat (agrupamento visual sem chave no model).
     */
    protected function isFlatSection($column): bool
    {
        return $column instanceof SectionField && $column->isFlat();
    }

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
     * Extrai as regras de validação de todos os campos (incluindo items de repeater)
     */
    public function getValidationRules($record = null, $request = null): array
    {
        $rules = [];

        foreach ($this->getColumns() as $column) {
            // Ignora colunas invisíveis
            if (method_exists($column, 'isVisible') && ! $column->isVisible()) {
                continue;
            }
            if (method_exists($column, 'isSheet') && $column->isSheet()) {
                continue;
            }

            // SectionField flat: ignorar o nome da seção e processar os campos-filhos diretamente
            if ($this->isFlatSection($column)) {
                $rules = array_merge($rules, $this->getValidationRulesForFields($column->getFields(), $record, $request));
                continue;
            }

            // Trata RepeaterField especialmente para coletar regras dos items
            if (method_exists($column, 'getItemsValidationRules')) {
                $itemRules = $column->getItemsValidationRules($record);
                $rules = array_merge($rules, $itemRules);
                // Continua para processar também as regras do repeater em si
            }

            $columnRules = $column->getRules($record);

            if(method_exists($column, 'getRealName')) {
                $rules[$column->getRealName()] = ['nullable'];
            }
            if (! empty($columnRules)) {
                if (method_exists($column, 'getFieldsUsing')) {
                    // Para campos que possuem fieldsUsing (ex: CascadingField), aplica regras em ambos os campos
                    $rules[$column->getFieldsUsing()] = $columnRules; 
                }
                if (! in_array($column->getType(), ['password'])) {
                    $rules[$column->getName()] = $columnRules;
                } else {
                    if ($request && $request->filled($column->getName())) {
                        // Se for um novo registro ou o campo de senha está vazio, aplica as regras
                        $rules[$column->getName()] = $columnRules;
                    }
                }
            } else {
                if (! in_array($column->getType(), ['password'])) {
                    $rules[$column->getName()] = ['nullable'];
                }
            }
        }

        return $rules;
    }

    /**
     * Extrai regras de validação de um array de campos (usado para os filhos de SectionField flat).
     */
    protected function getValidationRulesForFields(array $fields, $record = null, $request = null): array
    {
        $rules = [];

        foreach ($fields as $field) {
            if (method_exists($field, 'isVisible') && ! $field->isVisible()) {
                continue;
            }

            $fieldRules = $field->getRules($record);

            if (! empty($fieldRules)) {
                if (! in_array($field->getType(), ['password'])) {
                    $rules[$field->getName()] = $fieldRules;
                } elseif ($request && $request->filled($field->getName())) {
                    $rules[$field->getName()] = $fieldRules;
                }
            } else {
                if (! in_array($field->getType(), ['password'])) {
                    $rules[$field->getName()] = ['nullable'];
                }
            }
        }

        return $rules;
    }

    /**
     * Prepara os dados do request ANTES da validação
     *
     * Converte valores formatados (ex: money) para formato validável
     * Processa dados dos items do repeater com prepareItemsForValidation
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $model  Modelo existente (para edição)
     * @return array Dados preparados para validação
     */
    public function prepareDataForValidation($request, $model = null): array
    {
        $data = $request->all();

        // Aplica conversões necessárias antes da validação
        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();

            // SectionField flat: remove a chave fantasma da seção e garante que todos os
            // campos-filhos estejam presentes em $data (com null como padrão quando ausentes).
            // Sem isso, campos não preenchidos não são enviados pelo frontend e ficam fora
            // do $validated, quebrando saves quando o campo tem regra required.
            if ($this->isFlatSection($column)) {
                unset($data[$columnName]);
                foreach ($column->getFields() as $field) {
                    if (! array_key_exists($field->getName(), $data)) {
                        $data[$field->getName()] = null;
                    }
                }
                $data = $this->prepareFieldsForValidation($column->getFields(), $data, $model);
                continue;
            }

            // Verifica se o campo está presente no request
            if (! array_key_exists($columnName, $data)) {
                continue;
            }

            try {
                // Trata RepeaterField especialmente para preparar items
                if (method_exists($column, 'prepareItemsForValidation')) {
                    $data = $column->prepareItemsForValidation($data, $model);
                    // Continua para processar também o valueUsing do repeater em si
                }

                // Aplica valueUsing se existir (converte dados formatados)
                $valueUsing = $column->getValueUsing($data, $model);

                if ($valueUsing !== null) {
                    if (is_array($valueUsing)) {
                        $data = array_merge($data, $valueUsing);
                    } else {
                        $data[$columnName] = $valueUsing;
                    }
                }
            } catch (\Throwable $e) {
                logger()->warning("Error preparing field '{$columnName}' for validation: ".$e->getMessage());
                // Mantém o valor original
            }
        }

        return $data;
    }

    /**
     * Prepara os campos-filhos de uma SectionField flat para validação.
     */
    protected function prepareFieldsForValidation(array $fields, array $data, $model = null): array
    {
        foreach ($fields as $field) {
            $fieldName = $field->getName();

            if (! array_key_exists($fieldName, $data)) {
                continue;
            }

            try {
                $valueUsing = $field->getValueUsing($data, $model);

                if ($valueUsing !== null) {
                    if (is_array($valueUsing)) {
                        $data = array_merge($data, $valueUsing);
                    } else {
                        $data[$fieldName] = $valueUsing;
                    }
                }
            } catch (\Throwable $e) {
                logger()->warning("Error preparing section field '{$fieldName}' for validation: ".$e->getMessage());
            }
        }

        return $data;
    }

    /**
     * Extrai as mensagens de validação customizadas (incluindo items de repeater)
     */
    public function getValidationMessages(): array
    {
        $messages = [];

        foreach ($this->getColumns() as $column) {
            // SectionField flat: coletar mensagens dos campos-filhos
            if ($this->isFlatSection($column)) {
                foreach ($column->getFields() as $field) {
                    $fieldMessages = $field->getMessages();
                    if (! empty($fieldMessages)) {
                        foreach ($fieldMessages as $rule => $message) {
                            $messages["{$field->getName()}.{$rule}"] = $message;
                        }
                    }
                }
                continue;
            }

            // Coleta mensagens dos items do repeater se disponível
            if (method_exists($column, 'getItemsValidationMessages')) {
                $itemMessages = $column->getItemsValidationMessages();
                $messages = array_merge($messages, $itemMessages);
            }

            $columnMessages = $column->getMessages();

            if (! empty($columnMessages)) {
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
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $model  Modelo existente (para edição)
     * @return array Dados do formulário
     */
    public function getFormData($data, $model = null): array
    {
        // Aplica customizações apenas nos campos definidos
        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();

            // SectionField flat: remove a chave fantasma e processa os filhos no nível raiz.
            // Garante que campos ausentes recebam null para aparecerem no $validated.
            if ($this->isFlatSection($column)) {
                unset($data[$columnName]);
                foreach ($column->getFields() as $field) {
                    $fieldName = $field->getName();
                    if (! array_key_exists($fieldName, $data)) {
                        $data[$fieldName] = null;
                    }
                    try {
                        $valueUsing = $field->getValueUsing($data, $model);
                        if ($valueUsing !== null) {
                            if (is_array($valueUsing)) {
                                $data = array_merge($data, $valueUsing);
                            } else {
                                $data[$fieldName] = $valueUsing;
                            }
                        }
                    } catch (\Throwable $e) {
                        logger()->warning("Error processing section field '{$fieldName}': ".$e->getMessage());
                    }
                }
                continue;
            }

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
                logger()->warning("Error processing form field '{$columnName}': ".$e->getMessage());
            }
        }

        return $data;
    }

    /**
     * Retorna apenas os campos obrigatórios
     */
    public function getRequiredFields(): array
    {
        return array_filter($this->getColumns(), fn ($column) => $column->isRequired());
    }

    /**
     * Salva dados relacionados após salvar o modelo principal
     */
    public function saveRelatedData(array $data, $model, $request): void
    {
        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();
            $valueUsing = data_get($column->getValueUsing($data, $model), $columnName, data_get($data, $columnName));

            // Verifica se há dados para este campo
            if (! $valueUsing) {
                continue;
            }
            // Se o campo tem relacionamento definido explicitamente
            if ($column->hasRelationship()) {
                $relationship = $column->getRelationship();
                $this->handleExplicitRelationship($model, $relationship, $columnName, $valueUsing);

                continue;
            }

            // Detecta relacionamento automaticamente pelo método no model
            if (method_exists($model, $columnName)) {
                $this->handleAutoDetectedRelationship($model, $columnName, $valueUsing, $request);
            }
        }
    }

    /**
     * Atualiza dados relacionados após salvar o modelo principal
     */
    public function updateRelatedData(array $data, $model, $request): void
    {
        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();
            $valueUsing = data_get($column->getValueUsing($data, $model), $columnName, data_get($data, $columnName));

            // Verifica se há dados para este campo
            if (! $valueUsing) {
                continue;
            }

            // Se o campo tem relacionamento definido explicitamente
            if ($column->hasRelationship()) {
                $relationship = $column->getRelationship();
                $this->handleExplicitRelationship($model, $relationship, $columnName, $valueUsing);

                continue;
            }

            // Detecta relacionamento automaticamente pelo método no model
            if (method_exists($model, $columnName)) {
                $this->handleAutoDetectedRelationship($model, $columnName, $valueUsing, $request);
            }
        }
    }

    /**
     * Trata relacionamento definido explicitamente via relationship()
     */
    protected function handleExplicitRelationship($model, string $relationship, string $columnName, $value): void
    {
        if (! method_exists($model, $relationship)) {
            return;
        }

        $relationInstance = $model->$relationship();

        // BelongsToMany - Muitos para muitos
        if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
            $model->$relationship()->sync($value);
        }
        // HasMany - Um para muitos
        elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
            if (is_array($value)) {
                $this->syncHasManyRelation($model, $relationship, $value);
            }
        }
        // HasOne - Um para um
        elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
            $this->updateHasOneRelation($model, $relationship, $value);
        }
        // MorphMany - Polimórfico um para muitos
        elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphMany) {
            if (is_array($value)) {
                $this->syncMorphManyRelation($model, $relationship, $value);
            }
        }
        // MorphOne - Polimórfico um para um
        elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphOne) {
            $this->updateMorphOneRelation($model, $relationship, $value);
        }
        // MorphToMany - Polimórfico muitos para muitos
        elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphToMany) {
            $model->$relationship()->sync($value);
        }
    }

    /**
     * Detecta e trata relacionamento automaticamente pelo método do model
     */
    protected function handleAutoDetectedRelationship($model, string $methodName, $value, $request): void
    {
        try {
            // Tenta obter a instância do relacionamento
            $relationInstance = $model->$methodName();

            // Verifica se é realmente um relacionamento Eloquent
            if (! $relationInstance instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                // Não é um relacionamento, pode ser método customizado
                // Chama como método customizado passando valor e request
                $model->$methodName($value, $request);

                return;
            }

            // É um relacionamento Eloquent, trata automaticamente
            // BelongsToMany - Muitos para muitos
            if ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                $model->$methodName()->sync($value);
            }
            // HasMany - Um para muitos
            elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                if (is_array($value)) {
                    $this->syncHasManyRelation($model, $methodName, $value);
                }
            }
            // HasOne - Um para um
            elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                $this->updateHasOneRelation($model, $methodName, $value);
            }
            // MorphMany - Polimórfico um para muitos
            elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphMany) {
                if (is_array($value)) {
                    $this->syncMorphManyRelation($model, $methodName, $value);
                }
            }
            // MorphOne - Polimórfico um para um
            elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphOne) {
                $this->updateMorphOneRelation($model, $methodName, $value);
            }
            // MorphToMany - Polimórfico muitos para muitos
            elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\MorphToMany) {
                $model->$methodName()->sync($value);
            }
            // BelongsTo - Muitos para um (apenas atualiza foreign key, já tratado no save)
            elseif ($relationInstance instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                // BelongsTo é tratado automaticamente pelo Laravel no fillable
                // Não precisa fazer nada aqui
            }
        } catch (\Throwable $e) {
            // Se der erro ao obter relacionamento, loga e continua
            logger()->warning("Error handling relationship '{$methodName}': ".$e->getMessage());
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
        if (! empty($existingIds)) {
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
        if (! empty($existingIds)) {
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
