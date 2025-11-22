<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services\Http;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest; 

/**
 * Serviço base para integração com APIs externas
 * 
 * Fornece funcionalidades comuns para todos os serviços de API:
 * - Configuração de autenticação
 * - Tratamento de erros
 * - Logging padronizado
 * - Rate limiting
 * - Retry logic
 */
abstract class BaseApiService
{
    /**
     * Dados da integração
     */
    protected object $integration;

    /**
     * Timeout padrão para requisições (segundos)
     */
    protected int $timeout = 60;

    /**
     * Número máximo de tentativas
     */
    protected int $maxRetries = 3;

    /**
     * Delay entre tentativas (segundos)
     */
    protected int $retryDelay = 2;

    /**
     * Headers padrão para requisições
     */
    protected array $defaultHeaders = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];

    protected array $params = [];

    /**
     * Construtor do serviço base
     */
    public function __construct(object $integration)
    {
        $this->integration = $integration;
    }

    abstract public function getEndpoint(string $name): string;

    /**
     * Método abstrato para implementar validação específica de cada serviço
     */
    abstract protected function validateIntegration(): bool;

    /**
     * Método abstrato para extrair informações de paginação
     */
    abstract protected function extractPagination(array $response): ?array;

    public function discoverPagination($type,  array $params = []): ?array
    {
        $response = $this->makeRequest($this->getEndpoint($type),   $params);

        if (!$response) {
            return null;
        }

        return $response;
    }

    protected function getIntegration(): object
    {
        return $this->integration;
    }

    /**
     * Configura o cliente HTTP com autenticação e headers
     */
    protected function configureHttpClient(): PendingRequest
    {
        $client = Http::withHeaders($this->defaultHeaders)->baseUrl($this->getBaseUrl())
            ->timeout($this->timeout)
            ->retry($this->maxRetries, $this->retryDelay, function ($exception) {
                Log::error('Erro na requisição API', [
                    'message' => $exception->getMessage(),
                    'integration' => $this->integration->identifier,
                ]);
                return true; // Retry on any exception
            });


        $client->withHeaders($this->getHeaders());
        /**
         * Verificar métodos de autenticação
         */
        if (method_exists($this, 'getBasicAuthCredentials')) {
            $client->withBasicAuth(...$this->getBasicAuthCredentials());
        }

        if (method_exists($this, 'getBearerToken')) {
            $client->withToken($this->getBearerToken());
        }

        $client->withOptions($this->getOptions());

        return $client;
    }

    /**
     * Retorna a URL base da integração
     */
    public function getBaseUrl(): string
    {
        return $this->integration->api_url;
    }

    /**
     * Retorna os headers da integração
     */
    protected function getHeaders(): array
    {
        return $this->integration->authentication_headers ?? [];
    }

    /**
     * Retorna o método de autenticação
     */
    protected function getMethod(): string
    {
        return $this->integration->http_method ?? 'GET';
    }

    /**
     * Retorna os params para metodos de requisição
     */
    protected function getParams(): array
    {
        return [];
    }

    /**
     * Retorna o corpo da requisição
     */
    protected function getBody(): array
    {
        return [];
    }

    /**
     * Retorna as opções para a requisição
     */
    protected function getOptions(): array
    {
        return [];
    }

    /**
     * Faz uma requisição HTTP com retry automático
     */
    protected function makeRequest(string $endpoint, array $params = []): ?array
    {
        $client = $this->configureHttpClient();

        Log::info("Fazendo requisição para {$endpoint} com método {$this->getMethod()}", [
            'params' => array_merge($this->getBody(), $params),
            'integration' => $this->integration->identifier,
        ]);
        $response = match ($this->getMethod()) {
            'GET' => $client->get($endpoint, array_merge($this->getParams(), $params)),
            'POST' => $client->post($endpoint, array_merge($this->getBody(), $params)),
            'PUT' => $client->put($endpoint, array_merge($this->getBody(), $params)),
            'DELETE' => $client->delete($endpoint, array_merge($this->getParams(), $params)),
            default => throw new \Exception("Método HTTP {$this->getMethod()} não suportado"),
        };

        return $this->handleResponse($response);
    }

    /**
     * Trata a resposta da requisição
     */
    protected function handleResponse(Response $response): ?array
    {

        if ($response->successful()) {
            return $this->extractPagination($response->json());
        }

        Log::error('Erro na requisição API', [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
        ]);

        return [
            'page' => 0,
            'per_page' => 0,
            'total' => 0,
            'data' => [],
        ];
    }
}
