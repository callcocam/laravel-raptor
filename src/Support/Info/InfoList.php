<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Info;

use Callcocam\LaravelRaptor\Support\Concerns\FactoryPattern;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithActions;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithColumns;
use Illuminate\Database\Eloquent\Model;

class InfoList
{ 
   use WithColumns;
   use WithActions;
   use FactoryPattern;
    

    public function render(Model $data): array
    {
        $renderedData = [];
        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();
            $value = data_get($data, $columnName);
            $rendered = $column->render($value, $data);

            // Merge com informações da coluna para o Vue
            $columnData = is_array($rendered) ? $rendered : ['value' => $rendered];
            
            $renderedData[$columnName] = array_merge(
                $column->toArray(),
                $columnData,
                ['id' => $columnName]
            );

            // Se a coluna tem sub-colunas para renderizar (CardColumn)
            if (isset($renderedData[$columnName]['_columns_to_render'])) {
                $childColumns = $renderedData[$columnName]['_columns_to_render'];
                $renderedColumns = [];

                foreach ($childColumns as $childColumn) {
                    $childName = $childColumn->getName();
                    $childValue = data_get($data, $childName);

                    // Pula valores vazios
                    if ($childValue === null || $childValue === '') {
                        continue;
                    }

                    $childRendered = $childColumn->render($childValue, $data);
                    $childData = is_array($childRendered) ? $childRendered : ['value' => $childRendered];

                    $renderedColumns[$childName] = array_merge(
                        $childColumn->toArray(),
                        $childData,
                        ['id' => $childName]
                    );
                }

                // Substitui o array vazio de columns pelo renderizado
                $renderedData[$columnName]['columns'] = $renderedColumns;
                // Remove a metadata
                unset($renderedData[$columnName]['_columns_to_render']);
            }
        }

        return array_merge($renderedData, [
            'viewActions' => $this->getArrayActions(),
        ]);
    }
}
