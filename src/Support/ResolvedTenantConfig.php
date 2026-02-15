<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support;

use Callcocam\LaravelRaptor\Services\TenantConnectionService;
use Illuminate\Database\Eloquent\Model;

/**
 * Configuração única ao carregar o tenant (Model Tenant, ou Client/Store com resolver customizado).
 * Centraliza tenant_id, client_id, store_id (se houver) e qual conexão/banco usar.
 * Database resolvido pela hierarquia: Store > Client > Tenant (só muda a default se algum estiver preenchido).
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

    /**
     * Cria a config a partir do tenant e opcionalmente domainData.
     * Database segue a hierarquia: Store (se domainable) > Client (se domainable) > Tenant.
     * Se nenhum tiver database preenchido, database fica null e a conexão default não é alterada.
     */
    public static function from(Model $tenant, ?object $domainData = null): self
    {
        $domainableType = $domainData?->domainable_type ?? null;
        $domainableId = $domainData?->domainable_id ?? null;

        $database = app(TenantConnectionService::class)->resolveDatabase($tenant, $domainData);

        return new self(
            tenant: $tenant,
            tenantId: (string) $tenant->getKey(),
            database: $database !== null && $database !== '' ? $database : null,
            connectionName: config('database.default'),
            domainableType: $domainableType,
            domainableId: $domainableId,
        );
    }

    public function hasDedicatedDatabase(): bool
    {
        return ! empty($this->database);
    }

    /** Banco apenas do tenant (para a conexão landlord). Null se tenant não tiver database. */
    public function landlordDatabase(): ?string
    {
        $db = $this->tenant->getAttribute('database');

        return is_string($db) && $db !== '' ? $db : null;
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
