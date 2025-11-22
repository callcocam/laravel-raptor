<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services\Http\Auth;

use Callcocam\LaravelRaptor\Services\Http\BaseHttpService;
use Illuminate\Http\Client\PendingRequest;

class TokenAuthService extends BaseHttpService
{
    protected string $token;
    protected string $tokenType = 'Bearer';

    public function __construct(?string $baseUrl = null, ?string $token = null)
    {
        parent::__construct($baseUrl);

        if ($token) {
            $this->token = $token;
        }
    }

    /**
     * Define o token de autenticação
     */
    public function token(string $token, string $type = 'Bearer'): static
    {
        $this->token = $token;
        $this->tokenType = $type;

        return $this;
    }

    /**
     * Aplica autenticação por token (Bearer)
     */
    protected function authenticate(PendingRequest $request): PendingRequest
    {
        if (!empty($this->token)) {
            $request->withToken($this->token, $this->tokenType);
        }

        return $request;
    }

    /**
     * Faz login e armazena o token
     */
    public function login(string $endpoint, array $credentials): array
    {
        $response = $this->post($endpoint, $credentials);

        if ($response->successful()) {
            $data = $response->json();
            
            // Tenta encontrar o token na resposta (padrões comuns)
            $this->token = $data['access_token'] 
                ?? $data['token'] 
                ?? $data['data']['token'] 
                ?? '';

            return $data;
        }

        $response->throw();
    }

    /**
     * Faz logout (opcional - invalida o token)
     */
    public function logout(string $endpoint = '/logout'): bool
    {
        $response = $this->post($endpoint);

        return $response->successful();
    }

    /**
     * Retorna o token atual
     */
    public function getToken(): ?string
    {
        return $this->token ?? null;
    }

    /**
     * Verifica se tem token configurado
     */
    public function hasToken(): bool
    {
        return !empty($this->token);
    }
}
