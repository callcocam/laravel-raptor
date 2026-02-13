<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Form\Columns\Types;

use Callcocam\LaravelRaptor\Support\Form\Columns\Column;

/**
 * CloudflareDnsField - Campo para gerenciar registros DNS na Cloudflare.
 *
 * Permite listar zones, listar/criar/apagar registros (domínio/subdomínio)
 * via API Cloudflare (token configurado em config/raptor.cloudflare).
 *
 * @example
 * CloudflareDnsField::make('cloudflare_dns')
 *     ->label('DNS Cloudflare')
 *     ->helpText('Criar ou remover registros DNS (A, CNAME, etc.) na zone selecionada.')
 */
class CloudflareDnsField extends Column
{
    protected string $apiBaseUrl = '/cloudflare';

    public function __construct(string $name, ?string $label = null)
    {
        parent::__construct($name, $label ?? __('DNS Cloudflare'));
        $this->component('form-field-cloudflare-dns');
        $this->defaultUsing(fn ($model) => [
            'zone_id' => null,
            'records' => [],
        ]);
    }

    public function apiBaseUrl(string $url): static
    {
        $this->apiBaseUrl = rtrim($url, '/');

        return $this;
    }

    public function getApiBaseUrl(): string
    {
        return $this->apiBaseUrl;
    }

    public function toArray($model = null): array
    {
        $base = parent::toArray($model);
        $base['apiBaseUrl'] = $this->getApiBaseUrl();

        return $base;
    }
}
