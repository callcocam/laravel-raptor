<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Traits;

use Callcocam\LaravelRaptor\Events\DatabaseConnectionFailed;
use Callcocam\LaravelRaptor\Services\TenantConnectionService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait UsesTenantDatabase
{
    /**
     * Flag para evitar múltiplas notificações do mesmo erro
     * 
     * @var bool
     */
    protected static $connectionErrorNotified = false;

    /**
     * Retorna a conexão a ser usada pelo model (banco do tenant).
     * Com só landlord + default: usa a conexão default, que é alterada para o banco do tenant no contexto.
     */
    public function getConnectionName(): ?string
    {
        return config('raptor.database.tenant_connection_name', 'default');
    }

    /**
     * Obtém a instância da conexão do banco de dados.
     * Sobrescreve para validar e notificar erros quando a conexão for realmente usada.
     * 
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        $connectionName = $this->getConnectionName();
        
        $tenantConnectionName = config('raptor.database.tenant_connection_name', 'default');
        if ($connectionName === $tenantConnectionName) {
            $this->validateAndNotifyConnection($connectionName);
        }
        
        return parent::getConnection();
    }

    /**
     * Valida a conexão e dispara notificação se houver erro.
     * 
     * @param string $connectionName Nome da conexão
     * @return void
     */
    protected function validateAndNotifyConnection(string $connectionName): void
    {
        // Evita múltiplas notificações na mesma requisição
        if (static::$connectionErrorNotified) {
            return;
        }

        try {
            // Tenta obter a conexão para validar
            $connection = DB::connection($connectionName);
            $connection->getPdo();
            
            // Se chegou aqui, a conexão está OK
            return;
        } catch (\Exception $e) {
            // Se falhar, prepara e dispara a notificação
            // Não re-lança a exceção aqui, pois o Eloquent vai tentar usar a conexão
            // e vai capturar o erro naturalmente, então apenas notificamos
            $this->handleConnectionError($connectionName, $e);
        }
    }

    /**
     * Trata erros de conexão e dispara notificação.
     * 
     * @param string $connectionName Nome da conexão
     * @param \Exception $exception Exceção capturada
     * @return void
     */
    protected function handleConnectionError(string $connectionName, \Exception $exception): void
    {
        // Marca como notificado para evitar múltiplas notificações
        static::$connectionErrorNotified = true;

        // Obtém informações do banco de dados da conexão
        $database = Config::get("database.connections.{$connectionName}.database");
        
        if (!$database) {
            // Se não conseguir identificar o database, apenas loga
            Log::warning("Erro ao conectar na conexão '{$connectionName}': {$exception->getMessage()}");
            return;
        }

        // Obtém informações do erro usando o serviço
        $connectionService = app(TenantConnectionService::class);
        $errorInfo = $connectionService->getConnectionErrorInfo($database, $exception);

        // Flash message para requisições síncronas (se houver request)
        if (request() && request()->hasSession()) {
            request()->session()->flash('error', $errorInfo['message']);
        }

        // Broadcast para notificação em tempo real (se houver usuário autenticado)
        if (auth()->check()) {
            try {
                event(new DatabaseConnectionFailed(
                    database: $errorInfo['database'],
                    message: $errorInfo['message'],
                    isDatabaseNotFound: $errorInfo['is_database_not_found'],
                    userId: auth()->id(),
                    resolutionSteps: $errorInfo['resolution_steps']
                ));
            } catch (\Illuminate\Broadcasting\BroadcastException $e) {
                // Se o broadcast falhar (ex: Reverb não está rodando), apenas loga
                Log::warning("Não foi possível fazer broadcast da notificação de erro de conexão. Reverb pode não estar rodando.", [
                    'error' => $e->getMessage(),
                    'database' => $errorInfo['database'],
                    'user_id' => auth()->id(),
                ]);
            } catch (\Exception $e) {
                // Captura qualquer outro erro de broadcast
                Log::warning("Erro ao fazer broadcast da notificação de erro de conexão.", [
                    'error' => $e->getMessage(),
                    'database' => $errorInfo['database'],
                    'user_id' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Reseta a flag de notificação (útil para testes).
     * 
     * @return void
     */
    public static function resetConnectionErrorNotification(): void
    {
        static::$connectionErrorNotified = false;
    }
}
