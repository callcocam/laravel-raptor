<?php

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Jobs\ProcessImport;
use Illuminate\Database\Eloquent\Model;

class SimpleImportDispatcher
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function dispatch(
        string $fileName,
        string $modelClass,
        ?array $columnMapping,
        string $importClass,
        string $resourceName,
        int|string $userId
    ): array {
        $model = app($modelClass);
        $connectionName = $model->getConnectionName();
        $connectionConfig = config("database.connections.{$connectionName}");

        ProcessImport::dispatch(
            $fileName,
            $modelClass,
            $columnMapping,
            $importClass,
            $resourceName,
            $userId,
            $connectionName,
            $connectionConfig
        );

        return [
            'notification' => [
                'title' => 'Importação Iniciada',
                'text' => 'Sua importação está sendo processada. Você receberá uma notificação quando estiver concluída.',
                'type' => 'info',
            ],
        ];
    }
}
