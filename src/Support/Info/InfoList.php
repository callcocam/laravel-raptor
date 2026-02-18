<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Info;

use Callcocam\LaravelRaptor\Support\Cast\CastRegistry;
use Callcocam\LaravelRaptor\Support\Concerns\EvaluatesClosures;
use Callcocam\LaravelRaptor\Support\Concerns\FactoryPattern;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithActions;
use Callcocam\LaravelRaptor\Support\Concerns\Interacts\WithColumns;
use Callcocam\LaravelRaptor\Support\Concerns\Shared\BelongToRequest;
use Illuminate\Database\Eloquent\Model;

class InfoList
{
    use FactoryPattern;
    use WithActions;
    use WithColumns;
    use EvaluatesClosures;
    use BelongToRequest;

    public function __construct()
    {
        CastRegistry::initialize(); // Carrega formatadores padrão
    }

    public function render(Model $data): array
    {
        $renderedData = [];
        foreach ($this->getColumns() as $column) {
            $columnName = $column->getName();
            $value = data_get($data, $columnName);

            // Aplica cast automático se não tiver castCallback customizado
            $value = $this->applyCastIfAvailable($column, $value, $data);

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

                    // Aplica cast automático para sub-colunas

                    $childValue = $this->applyCastIfAvailable($childColumn, $childValue, $data);

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

    /**
     * Aplica cast automático baseado no tipo da coluna
     */
    protected function applyCastIfAvailable($column, $value, $data)
    {
        // Se tem castCallback customizado, usa ele
        if ($column->hasCastCallback()) {
            return $column->getCastCallback($value, $data);
        }

        // Detecta o tipo de cast baseado no tipo da coluna
        $type = $column->getType();

        // Mapeamento de tipos de coluna para nomes de campo
        // O CastRegistry já tem formatadores registrados por nome de campo
        $fieldNameMap = [
            'date' => 'created_at',      // Usa o formatter de data
            'datetime' => 'updated_at',   // Usa o formatter de datetime
            'time' => 'time',
            'boolean' => 'active',        // Usa o formatter de boolean
            'status' => 'status',
            'email' => 'email',
            'phone' => 'phone',
            'currency' => 'price',        // Usa o formatter de currency
            'number' => 'count',
        ];

        // Se existe um cast para o tipo, usa o CastRegistry.resolve()
        if (isset($fieldNameMap[$type])) {
            $fieldName = $fieldNameMap[$type];
            $formatter = CastRegistry::resolve($value, $fieldName, ['data' => $data]);

            if ($formatter && method_exists($formatter, 'render')) {
                return $formatter->render();
            }
        }

        return $value;
    }
}
