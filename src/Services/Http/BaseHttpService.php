<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseHttpService
{
    protected string $baseUrl;
    protected int $timeout = 30;
    protected int $retryTimes = 3;
    protected int $retryDelay = 100; // milliseconds
    protected array $headers = [];
    protected array $options = [];

    public function __construct(?string $baseUrl = null)
    {
        if ($baseUrl) {
            $this->baseUrl = $baseUrl;
        }
    }

    /**
     * Define a URL base
     */
    public function baseUrl(string $url): static
    {
        $this->baseUrl = $url;

        return $this;
    }

    /**
     * Define o timeout da requisição
     */
    public function timeout(int $seconds): static
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Define configurações de retry
     */
    public function retry(int $times, int $delayMs = 100): static
    {
        $this->retryTimes = $times;
        $this->retryDelay = $delayMs;

        return $this;
    }

    /**
     * Adiciona headers customizados
     */
    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    /**
     * Adiciona opções adicionais
     */
    public function withOptions(array $options): static
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Cria a instância do PendingRequest configurada
     */
    protected function buildRequest(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retryDelay);

        if (!empty($this->headers)) {
            $request->withHeaders($this->headers);
        }

        if (!empty($this->options)) {
            $request->withOptions($this->options);
        }

        // Aplica autenticação (implementado nas classes filhas)
        $request = $this->authenticate($request);

        return $request;
    }

    /**
     * Método abstrato para autenticação
     * Deve ser implementado pelas classes filhas
     */
    abstract protected function authenticate(PendingRequest $request): PendingRequest;

    /**
     * Faz uma requisição GET
     */
    public function get(string $endpoint, array $query = []): Response
    {
        return $this->buildRequest()->get($endpoint, $query);
    }

    /**
     * Faz uma requisição POST
     */
    public function post(string $endpoint, array $data = []): Response
    {
        return $this->buildRequest()->post($endpoint, $data);
    }

    /**
     * Faz uma requisição PUT
     */
    public function put(string $endpoint, array $data = []): Response
    {
        return $this->buildRequest()->put($endpoint, $data);
    }

    /**
     * Faz uma requisição PATCH
     */
    public function patch(string $endpoint, array $data = []): Response
    {
        return $this->buildRequest()->patch($endpoint, $data);
    }

    /**
     * Faz uma requisição DELETE
     */
    public function delete(string $endpoint, array $data = []): Response
    {
        return $this->buildRequest()->delete($endpoint, $data);
    }

    /**
     * Verifica se a resposta foi bem-sucedida
     */
    protected function isSuccessful(Response $response): bool
    {
        return $response->successful();
    }

    /**
     * Lança exceção se a resposta falhar
     */
    protected function throwIfFailed(Response $response): Response
    {
        return $response->throw();
    }

    /**
     * Retorna os dados JSON da resposta
     */
    protected function json(Response $response, ?string $key = null): mixed
    {
        return $response->json($key);
    }
}
