<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuração única ao carregar o tenant (Model Tenant, ou Client/Store com resolver customizado).
 * Centraliza tenant_id, client_id, store_id (se houver) e qual conexão/banco usar.
 * O pacote usa apenas a conexão default; conexões client/store exigem resolver customizado.
 */
final class ResolvedTenantConfig
{
    public function __construct(
        public Model $tenant,
        public string $tenantId,
        public ?string $database = null,
        public ?string $connectionName = null,
        public ?string $domainableType = null,
        public ?string $domainableId = null,
    ) {
        $this->connectionName ??= config('database.default');
    }

    /** Cria a config a partir do tenant e opcionalmente domainData (domainable_type, domainable_id). */
    public static function from(Model $tenant, ?object $domainData = null): self
    {
        $database = $tenant->getAttribute('database');
        $domainableType = $domainData?->domainable_type ?? null;
        $domainableId = $domainData?->domainable_id ?? null;

        return new self(
            tenant: $tenant,
            tenantId: (string) $tenant->getKey(),
            database: is_string($database) ? $database : null,
            connectionName: config('database.default'),
            domainableType: $domainableType,
            domainableId: $domainableId,
        );
    }

    public function hasDedicatedDatabase(): bool
    {
        return ! empty($this->database);
    }

    /** ID do client (quando domainableType for Client). Resolver customizado preenche. */
    public function clientId(): ?string
    {
        if ($this->domainableType && $this->domainableId && str_ends_with((string) $this->domainableType, 'Client')) {
            return $this->domainableId;
        }

        return null;
    }

    /** ID do store (quando domainableType for Store). Resolver customizado preenche. */
    public function storeId(): ?string
    {
        if ($this->domainableType && $this->domainableId && str_ends_with((string) $this->domainableType, 'Store')) {
            return $this->domainableId;
        }

        return null;
    }

    /** Dados para aplicar no app/config (current_tenant_id, current_client_id, current_store_id, etc.). */
    public function toAppConfig(): array
    {
        $out = [
            'app.context' => 'tenant',
            'app.current_tenant_id' => $this->tenantId,
            'app.current_domainable_type' => $this->domainableType,
            'app.current_domainable_id' => $this->domainableId,
        ];
        if ($this->clientId() !== null) {
            $out['app.current_client_id'] = $this->domainableId;
        }
        if ($this->storeId() !== null) {
            $out['app.current_store_id'] = $this->domainableId;
        }

        return $out;
    }
}
