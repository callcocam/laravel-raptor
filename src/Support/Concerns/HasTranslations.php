<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Concerns;

use Callcocam\LaravelRaptor\Services\TranslationService;
use Illuminate\Support\Str;

/**
 * Trait para facilitar traduções nos controllers
 */
trait HasTranslations
{
    /**
     * Traduz uma chave usando o sistema de traduções customizado
     *
     * @param  string  $key  Chave de tradução
     * @param  array  $replace  Parâmetros para substituição
     * @param  string|null  $locale  Locale específico (null = app locale)
     */
    protected function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $tenantId = $this->getTenantId();

        // Obtém o grupo e a chave
        $parts = explode('.', $key, 2);
        $group = count($parts) === 2 ? $parts[0] : null;
        $translationKey = count($parts) === 2 ? $parts[1] : $key;

        // Tenta buscar tradução customizada
        $translationService = app(TranslationService::class);
        $customTranslation = $translationService->get($tenantId, $group, $translationKey, $locale);

        // Se não houver tradução customizada, usa a do Laravel
        $translation = $customTranslation ?? __($key, $replace, $locale);

        // Aplica substituições se houver
        if (! empty($replace)) {
            foreach ($replace as $search => $value) {
                $translation = str_replace(":{$search}", $value, $translation);
            }
        }

        return $translation;
    }

    /**
     * Traduz automaticamente labels do recurso
     *
     * @param  string  $type  Tipo: 'singular', 'plural', 'create', 'edit', 'delete', etc
     */
    protected function translateResource(string $type): string
    {
        $resourceName = $this->getResourceName();

        if (! $resourceName) {
            return ucfirst($type);
        }

        $group = Str::plural(Str::snake($resourceName));

        $keys = [
            'singular' => "{$group}.{$resourceName}",
            'plural' => "{$group}.".Str::plural($resourceName),
            'create' => "{$group}.create_{$resourceName}",
            'edit' => "{$group}.edit_{$resourceName}",
            'delete' => "{$group}.delete_{$resourceName}",
            'view' => "{$group}.view_{$resourceName}",
            'list' => "{$group}.list_".Str::plural($resourceName),
        ];

        $key = $keys[$type] ?? $keys['singular'];

        return $this->translate($key);
    }

    /**
     * Gera chave de tradução baseada no nome do controller
     *
     * @param  string  $suffix  Sufixo da chave (ex: 'name', 'description')
     */
    protected function getTranslationKey(string $suffix): string
    {
        $resourceName = $this->getResourceName();

        if (! $resourceName) {
            return $suffix;
        }

        $group = Str::plural(Str::snake($resourceName));

        return "{$group}.{$suffix}";
    }

    /**
     * Obtém o nome do recurso (deve ser implementado no controller)
     */
    abstract protected function getResourceName(): ?string;

    /**
     * Obtém o ID do tenant atual (deve ser implementado se usar multi-tenancy)
     */
    protected function getTenantId(): ?string
    {
        // Pode ser sobrescrito nos controllers
        return request()->header('X-Tenant-Id') ?? session('tenant_id');
    }

    /**
     * Traduz labels de campos do formulário
     *
     * @param  string  $field  Nome do campo
     */
    protected function translateField(string $field): string
    {
        $resourceName = $this->getResourceName();

        if (! $resourceName) {
            return ucfirst(str_replace('_', ' ', $field));
        }

        $group = Str::plural(Str::snake($resourceName));

        return $this->translate("{$group}.fields.{$field}");
    }

    /**
     * Traduz mensagens de validação
     *
     * @param  string  $rule  Nome da regra (ex: 'required', 'unique')
     * @param  string  $field  Nome do campo
     */
    protected function translateValidation(string $rule, string $field): string
    {
        $fieldLabel = $this->translateField($field);

        return $this->translate("validation.{$rule}", ['attribute' => $fieldLabel]);
    }

    /**
     * Traduz mensagens de ação (success, error, etc)
     *
     * @param  string  $action  Ação: 'created', 'updated', 'deleted'
     * @param  string  $status  Status: 'success', 'error'
     */
    protected function translateMessage(string $action, string $status = 'success'): string
    {
        $resourceLabel = $this->translateResource('singular');

        return $this->translate("messages.{$action}_{$status}", ['resource' => $resourceLabel]);
    }

    /**
     * Cria estrutura de traduções para um recurso
     * Útil para gerar as chaves necessárias
     */
    protected function generateTranslationStructure(): array
    {
        $resourceName = $this->getResourceName();

        if (! $resourceName) {
            return [
                'group' => null,
                'keys' => [],
            ];
        }

        $group = Str::plural(Str::snake($resourceName));

        return [
            'group' => $group,
            'keys' => [
                $resourceName => Str::title(str_replace('_', ' ', $resourceName)),
                Str::plural($resourceName) => Str::plural(Str::title(str_replace('_', ' ', $resourceName))),
                "create_{$resourceName}" => 'Criar '.Str::title(str_replace('_', ' ', $resourceName)),
                "edit_{$resourceName}" => 'Editar '.Str::title(str_replace('_', ' ', $resourceName)),
                "delete_{$resourceName}" => 'Excluir '.Str::title(str_replace('_', ' ', $resourceName)),
                "view_{$resourceName}" => 'Visualizar '.Str::title(str_replace('_', ' ', $resourceName)),
                'list_'.Str::plural($resourceName) => 'Listar '.Str::plural(Str::title(str_replace('_', ' ', $resourceName))),
            ],
        ];
    }

    /**
     * Registra traduções no banco de dados via TranslationService
     *
     * @param  array  $translations  Array de traduções: ['key' => 'value']
     * @param  string  $locale  Locale
     * @param  string|null  $tenantId  ID do tenant (null = global)
     * @return int Número de traduções registradas
     */
    protected function registerTranslations(array $translations, string $locale = 'pt_BR', ?string $tenantId = null): int
    {
        $translationService = app(TranslationService::class);
        $structure = $this->generateTranslationStructure();
        $group = $structure['group'];

        $count = 0;

        foreach ($translations as $key => $value) {
            $translationService->setOverride($tenantId, $group, $key, $value, $locale);
            $count++;
        }

        return $count;
    }
}
