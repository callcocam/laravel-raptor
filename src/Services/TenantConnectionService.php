<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantConnectionService
{
    /**
     * Configura a conexão de banco de dados seguindo a hierarquia:
     * Store (maior prioridade) > Client > Tenant > Default
     * 
     * @param mixed $tenant Instância do modelo Tenant
     * @param object $domainData Dados do domínio (domainable_type, domainable_id, etc)
     * @return bool True se a conexão foi configurada com sucesso, false caso contrário
     */
    public function configureTenantDatabase($tenant, $domainData): bool
    {
        $database = $this->resolveDatabase($tenant, $domainData);
        
        // Se não encontrou nenhum database configurado, não configura conexão
        if (empty($database)) {
            return false;
        }

        // Verifica se a conexão 'tenant' já existe
        $connections = Config::get('database.connections', []);
        
        if (!isset($connections['tenant'])) {
            // Cria a conexão 'tenant' baseada na conexão padrão
            return $this->createTenantConnection($database);
        } else {
            // Atualiza o database da conexão existente se mudou
            $currentDatabase = Config::get('database.connections.tenant.database');
            if ($currentDatabase !== $database) {
                Config::set('database.connections.tenant.database', $database);
                
                // Reconecta
                try {
                    DB::connection('tenant')->reconnect();
                    return true;
                } catch (\Exception $e) {
                    // Se falhar, recria a conexão
                    return $this->createTenantConnection($database);
                }
            }
        }

        return true;
    }

    /**
     * Resolve qual database usar seguindo a hierarquia:
     * Store > Client > Tenant
     * 
     * @param mixed $tenant Instância do modelo Tenant
     * @param object $domainData Dados do domínio
     * @return string|null Nome do database ou null se não encontrado
     */
    public function resolveDatabase($tenant, $domainData): ?string
    {
        $database = null;

        // Prioridade 1: Store (mais alta)
        if ($domainData->domainable_type === 'App\\Models\\Store' && $domainData->domainable_id) {
            $store = \App\Models\Store::find($domainData->domainable_id);
            if ($store) {
                if (!empty($store->database)) {
                    $database = $store->database;
                } elseif ($store->client_id) {
                    // Se Store não tem database, verifica o Client associado
                    $client = \App\Models\Client::find($store->client_id);
                    if ($client && !empty($client->database)) {
                        $database = $client->database;
                    }
                }
            }
        }

        // Prioridade 2: Client (quando domainable é Client diretamente)
        if (!$database && $domainData->domainable_type === 'App\\Models\\Client' && $domainData->domainable_id) {
            $client = \App\Models\Client::find($domainData->domainable_id);
            if ($client && !empty($client->database)) {
                $database = $client->database;
            }
        }

        // Prioridade 3: Tenant
        if (!$database && !empty($tenant->database)) {
            $database = $tenant->database;
        }

        return $database;
    }

    /**
     * Cria a conexão 'tenant' baseada na conexão padrão.
     * 
     * @param string $database Nome do banco de dados
     * @return bool True se a conexão foi criada com sucesso, false caso contrário
     */
    public function createTenantConnection(string $database): bool
    {
        $defaultConnection = Config::get('database.default');
        $defaultConfig = Config::get("database.connections.{$defaultConnection}", []);

        // Cria uma cópia da conexão padrão com o nome do banco do tenant
        $tenantConfig = array_merge($defaultConfig, [
            'database' => $database,
        ]);
        Config::set('database.connections.tenant', $tenantConfig);

        // Tenta conectar para validar
        try {
            DB::connection('tenant')->getPdo();
            return true;
        } catch (\Exception $e) {
            // Se falhar, remove a conexão do array de conexões
            $connections = Config::get('database.connections', []);
            unset($connections['tenant']);
            Config::set('database.connections', $connections);
            
            // Log do erro
            Log::warning("Não foi possível conectar ao banco de dados do tenant: {$database}. Erro: {$e->getMessage()}");
            
            return false;
        }
    }

    /**
     * Valida se a conexão 'tenant' está disponível e funcional.
     * 
     * @return bool True se a conexão está disponível, false caso contrário
     */
    public function isConnectionAvailable(): bool
    {
        if (!Config::has('database.connections.tenant')) {
            return false;
        }

        try {
            DB::connection('tenant')->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtém informações sobre o erro de conexão para notificação.
     * 
     * @param string $database Nome do banco de dados
     * @param \Exception $exception Exceção capturada
     * @return array Array com 'message', 'is_database_not_found' e 'resolution_steps'
     */
    public function getConnectionErrorInfo(string $database, \Exception $exception): array
    {
        $errorMessage = strtolower($exception->getMessage());
        $isDatabaseNotFound = str_contains($errorMessage, "does not exist") 
            || str_contains($errorMessage, "unknown database") 
            || str_contains($errorMessage, "database") && (str_contains($errorMessage, "not found") || str_contains($errorMessage, "não existe"))
            || str_contains($errorMessage, "sqlstate[42000]") && str_contains($errorMessage, "database")
            || str_contains($errorMessage, "1049") // MySQL error code for unknown database
            || str_contains($errorMessage, "3d000"); // PostgreSQL error code for invalid catalog name
        
        // Prepara mensagem e passos de resolução
        if ($isDatabaseNotFound) {
            $message = "O banco de dados '{$database}' não existe.";
            $resolutionSteps = [
                "Verifique se o banco de dados '{$database}' foi criado no servidor",
                "Confirme se o nome do banco está correto nas configurações do tenant/cliente/loja",
                "Execute o comando SQL para criar o banco: CREATE DATABASE `{$database}`;",
                "Verifique as permissões do usuário do banco de dados",
                "Se o problema persistir, entre em contato com o suporte técnico"
            ];
        } else {
            // Trunca a mensagem de erro para não ser muito longa
            $shortError = strlen($exception->getMessage()) > 150 
                ? substr($exception->getMessage(), 0, 150) . '...' 
                : $exception->getMessage();
            $message = "Não foi possível conectar ao banco de dados '{$database}'. Erro: {$shortError}";
            $resolutionSteps = [
                "Verifique se o servidor de banco de dados está em execução",
                "Confirme se as credenciais de acesso estão corretas",
                "Verifique se o banco de dados '{$database}' existe",
                "Confirme se o usuário do banco tem permissões adequadas",
                "Verifique a conectividade de rede com o servidor de banco de dados",
                "Se o problema persistir, entre em contato com o suporte técnico"
            ];
        }

        return [
            'message' => $message,
            'is_database_not_found' => $isDatabaseNotFound,
            'resolution_steps' => $resolutionSteps,
            'database' => $database,
        ];
    }
}

