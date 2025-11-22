<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services\Http\Auth;

use Callcocam\LaravelRaptor\Services\Http\BaseHttpService;
use Illuminate\Http\Client\PendingRequest;

class BasicAuthService extends BaseHttpService
{
    protected string $username;
    protected string $password;

    public function __construct(?string $baseUrl = null, ?string $username = null, ?string $password = null)
    {
        parent::__construct($baseUrl);

        if ($username) {
            $this->username = $username;
        }

        if ($password) {
            $this->password = $password;
        }
    }

    /**
     * Define as credenciais de autenticação básica
     */
    public function credentials(string $username, string $password): static
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * Aplica autenticação básica (HTTP Basic Auth)
     */
    protected function authenticate(PendingRequest $request): PendingRequest
    {
        if (!empty($this->username) && !empty($this->password)) {
            $request->withBasicAuth($this->username, $this->password);
        }

        return $request;
    }

    /**
     * Testa a conexão com as credenciais atuais
     */
    public function testConnection(string $endpoint = '/'): bool
    {
        try {
            $response = $this->get($endpoint);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verifica se tem credenciais configuradas
     */
    public function hasCredentials(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }
}
