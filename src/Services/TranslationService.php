<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Services;

use Callcocam\LaravelRaptor\Models\TranslationOverride;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service responsável por gerenciar traduções customizadas por tenant.
 *
 * Implementa sistema de prioridade: Tenant Override > Global Override > Laravel Lang Files
 *
 * @package Callcocam\LaravelRaptor\Services
 * @author Claudio Campos <callcocam@gmail.com>
 */
class TranslationService
{
    /**
     * Cache em memória para evitar múltiplas queries no mesmo request
     */
    private static array $runtimeCache = [];

    /**
     * Tempo de cache em segundos (padrão: 1 hora)
     */
    private int $cacheTtl;

    /**
     * Prefixo para chaves de cache
     */
    private string $cachePrefix;

    /**
     * Habilita/desabilita cache
     */
    private bool $cacheEnabled;

    public function __construct()
    {
        $this->cacheTtl = config('raptor.translation.cache_ttl', 3600);
        $this->cachePrefix = config('raptor.translation.cache_prefix', 'translation');
        $this->cacheEnabled = config('raptor.translation.cache_enabled', true);
    }

    /**
     * Obtém a tradução customizada com fallback automático
     *
     * Ordem de prioridade:
     * 1. Override do tenant atual
     * 2. Override global (tenant_id = null) em DB
     * 3. null (permite Laravel usar arquivo de lang)
     *
     * @param string|null $tenantId ID do tenant atual
     * @param string|null $group Grupo da tradução (ex: 'products')
     * @param string $key Chave da tradução (ex: 'product')
     * @param string $locale Locale (ex: 'pt_BR')
     * @return string|null Valor da tradução ou null para usar fallback do Laravel
     */
    public function get(?string $tenantId, ?string $group, string $key, string $locale): ?string
    {
        // Verifica cache em memória (runtime)
        $runtimeKey = $this->getRuntimeCacheKey($tenantId, $group, $key, $locale);

        if (isset(self::$runtimeCache[$runtimeKey])) {
            return self::$runtimeCache[$runtimeKey];
        }

        // Verifica cache persistente
        if ($this->cacheEnabled) {
            $cacheKey = $this->getCacheKey($tenantId, $group, $key, $locale);
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                return self::$runtimeCache[$runtimeKey] = $cached;
            }
        }

        // Busca no banco de dados
        $value = $this->fetchFromDatabase($tenantId, $group, $key, $locale);

        // Armazena nos caches
        self::$runtimeCache[$runtimeKey] = $value;

        if ($this->cacheEnabled && $value !== null) {
            Cache::put(
                $this->getCacheKey($tenantId, $group, $key, $locale),
                $value,
                $this->cacheTtl
            );
        }

        return $value;
    }

    /**
     * Busca tradução no banco de dados com prioridade Tenant > Global
     *
     * @param string|null $tenantId
     * @param string|null $group
     * @param string $key
     * @param string $locale
     * @return string|null
     */
    protected function fetchFromDatabase(?string $tenantId, ?string $group, string $key, string $locale): ?string
    {
        $groupTable = config('raptor.tables.translation_groups', 'translation_groups');
        $overridesTable = config('raptor.tables.translation_overrides', 'translation_overrides');

        // Buscar override com JOIN na tabela pai
        $override = TranslationOverride::query()
            ->join(
                $groupTable,
                "{$overridesTable}.translation_group_id",
                '=',
                "{$groupTable}.id"
            )
            ->where("{$groupTable}.locale", $locale)
            ->where("{$groupTable}.group", $group)
            ->where("{$overridesTable}.key", $key)
            ->when($tenantId, fn($q) => $q->where("{$groupTable}.tenant_id", $tenantId))
            // Prioriza tenant sobre global
            ->orderByRaw("CASE WHEN {$groupTable}.tenant_id IS NOT NULL THEN 1 ELSE 2 END")
            ->select("{$overridesTable}.value")
            ->first();

        return $override?->value;
    }

    /**
     * Carrega todas as traduções de um tenant em batch (otimização)
     *
     * @param string|null $tenantId
     * @param string $locale
     * @return array Array associativo ['group.key' => 'value']
     */
    public function loadAllForTenant(?string $tenantId, string $locale): array
    {
        $cacheKey = "{$this->cachePrefix}:all:{$tenantId}:{$locale}";

        if ($this->cacheEnabled) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $translations = [];

        // Busca traduções do tenant via JOIN
        if ($tenantId) {
            $tenantTranslations = TranslationOverride::query()
                ->with('group')
                ->whereHas('group', function ($query) use ($tenantId, $locale) {
                    $query->where('tenant_id', $tenantId)
                        ->where('locale', $locale);
                })
                ->get();

            foreach ($tenantTranslations as $translation) {
                $fullKey = $translation->full_key;
                $translations[$fullKey] = $translation->value;
            }
        }

        // Busca traduções globais (que não foram sobrescritas pelo tenant)
        $globalTranslations = TranslationOverride::query()
            ->with('group')
            ->whereHas('group', function ($query) use ($locale) {
                $query->whereNull('tenant_id')
                    ->where('locale', $locale);
            })
            ->get();

        foreach ($globalTranslations as $translation) {
            $fullKey = $translation->full_key;

            // Só adiciona se não foi sobrescrito pelo tenant
            if (!isset($translations[$fullKey])) {
                $translations[$fullKey] = $translation->value;
            }
        }

        if ($this->cacheEnabled) {
            Cache::put($cacheKey, $translations, $this->cacheTtl);
        }

        return $translations;
    }

    /**
     * Invalida cache de um tenant específico
     *
     * @param string|null $tenantId
     * @param string|null $locale Locale específico ou null para todos
     * @return void
     */
    public function clearCache(?string $tenantId = null, ?string $locale = null): void
    {
        // Limpa runtime cache
        self::$runtimeCache = [];

        if (!$this->cacheEnabled) {
            return;
        }

        if ($tenantId === null && $locale === null) {
            // Limpa todo o cache de traduções
            Cache::flush();
            return;
        }

        if ($locale === null) {
            // Limpa todas as locales de um tenant
            $locales = config('raptor.translation.available_locales', ['pt_BR', 'en', 'es']);
            foreach ($locales as $loc) {
                Cache::forget("{$this->cachePrefix}:all:{$tenantId}:{$loc}");
            }
        } else {
            // Limpa locale específico de um tenant
            Cache::forget("{$this->cachePrefix}:all:{$tenantId}:{$locale}");
        }
    }

    /**
     * Cria ou atualiza uma tradução override
     *
     * @param string|null $tenantId
     * @param string|null $group
     * @param string $key
     * @param string $locale
     * @param string $value
     * @return TranslationOverride
     */
    public function setOverride(
        ?string $tenantId,
        ?string $group,
        string $key,
        string $locale,
        string $value
    ): TranslationOverride {
        // 1. Encontra ou cria o grupo pai
        $translationGroup = \Callcocam\LaravelRaptor\Models\TranslationGroup::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'group' => $group,
                'locale' => $locale,
            ]
        );

        // 2. Cria ou atualiza o override
        $override = TranslationOverride::updateOrCreate(
            [
                'translation_group_id' => $translationGroup->id,
                'key' => $key,
            ],
            [
                'value' => $value,
            ]
        );

        // Invalida cache
        $this->clearCache($tenantId, $locale);

        return $override;
    }

    /**
     * Remove uma tradução override
     *
     * @param string|null $tenantId
     * @param string|null $group
     * @param string $key
     * @param string $locale
     * @return bool
     */
    public function deleteOverride(
        ?string $tenantId,
        ?string $group,
        string $key,
        string $locale
    ): bool {
        $deleted = TranslationOverride::query()
            ->whereHas('group', function ($query) use ($tenantId, $group, $locale) {
                $query->where('tenant_id', $tenantId)
                    ->where('group', $group)
                    ->where('locale', $locale);
            })
            ->where('key', $key)
            ->delete();

        if ($deleted) {
            $this->clearCache($tenantId, $locale);
        }

        return $deleted > 0;
    }

    /**
     * Gera chave de cache persistente
     *
     * @param string|null $tenantId
     * @param string|null $group
     * @param string $key
     * @param string $locale
     * @return string
     */
    protected function getCacheKey(?string $tenantId, ?string $group, string $key, string $locale): string
    {
        $tenant = $tenantId ?? 'global';
        $grp = $group ?? 'null';

        return "{$this->cachePrefix}:{$tenant}:{$grp}:{$key}:{$locale}";
    }

    /**
     * Gera chave de cache em memória (runtime)
     *
     * @param string|null $tenantId
     * @param string|null $group
     * @param string $key
     * @param string $locale
     * @return string
     */
    protected function getRuntimeCacheKey(?string $tenantId, ?string $group, string $key, string $locale): string
    {
        return $this->getCacheKey($tenantId, $group, $key, $locale);
    }

    /**
     * Obtém estatísticas de uso de traduções
     *
     * @return array
     */
    public function getStats(): array
    {
        $groupTable = config('raptor.tables.translation_groups', 'translation_groups');

        return [
            'total_overrides' => TranslationOverride::count(),
            'global_overrides' => TranslationOverride::query()
                ->whereHas('group', fn($q) => $q->whereNull('tenant_id'))
                ->count(),
            'tenant_overrides' => TranslationOverride::query()
                ->whereHas('group', fn($q) => $q->whereNotNull('tenant_id'))
                ->count(),
            'locales' => DB::table($groupTable)
                ->select('locale')
                ->distinct()
                ->pluck('locale')
                ->toArray(),
            'groups' => DB::table($groupTable)
                ->select('group')
                ->distinct()
                ->whereNotNull('group')
                ->pluck('group')
                ->toArray(),
            'runtime_cache_size' => count(self::$runtimeCache),
        ];
    }
}
