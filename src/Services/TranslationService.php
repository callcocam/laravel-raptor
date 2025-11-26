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
     * @param string|null $name Nome do grupo da tradução (ex: 'products')
     * @param string $key Chave da tradução (ex: 'product')
     * @param string $locale Locale (ex: 'pt_BR')
     * @return string|null Valor da tradução ou null para usar fallback do Laravel
     */
    public function get(?string $tenantId, ?string $name, string $key, string $locale): ?string
    {
        // Verifica cache em memória (runtime)
        $runtimeKey = $this->getRuntimeCacheKey($tenantId, $name, $key, $locale);

        if (isset(self::$runtimeCache[$runtimeKey])) {
            return self::$runtimeCache[$runtimeKey];
        }

        // Verifica cache persistente
        if ($this->cacheEnabled) {
            $cacheKey = $this->getCacheKey($tenantId, $name, $key, $locale);
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                return self::$runtimeCache[$runtimeKey] = $cached;
            }
        }

        // Busca no banco de dados
        $value = $this->fetchFromDatabase($tenantId, $name, $key, $locale);

        // Armazena nos caches
        self::$runtimeCache[$runtimeKey] = $value;

        if ($this->cacheEnabled && $value !== null) {
            Cache::put(
                $this->getCacheKey($tenantId, $name, $key, $locale),
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
     * @param string|null $name
     * @param string $key
     * @param string $locale
     * @return string|null
     */
    protected function fetchFromDatabase(?string $tenantId, ?string $name, string $key, string $locale): ?string
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
            ->where("{$groupTable}.name", $name)
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
     * @param string|null $name
     * @param string $key
     * @param string $locale
     * @param string $value
     * @return TranslationOverride
     */
    public function setOverride(
        ?string $tenantId,
        ?string $name,
        string $key,
        string $locale,
        string $value
    ): TranslationOverride {
        // 1. Encontra ou cria o grupo pai
        $translationGroup = \Callcocam\LaravelRaptor\Models\TranslationGroup::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => $name,
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
     * @param string|null $name
     * @param string $key
     * @param string $locale
     * @return bool
     */
    public function deleteOverride(
        ?string $tenantId,
        ?string $name,
        string $key,
        string $locale
    ): bool {
        $deleted = TranslationOverride::query()
            ->whereHas('group', function ($query) use ($tenantId, $name, $locale) {
                $query->where('tenant_id', $tenantId)
                    ->where('name', $name)
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
     * @param string|null $name
     * @param string $key
     * @param string $locale
     * @return string
     */
    protected function getCacheKey(?string $tenantId, ?string $name, string $key, string $locale): string
    {
        $tenant = $tenantId ?? 'global';
        $grp = $name ?? 'null';

        return "{$this->cachePrefix}:{$tenant}:{$grp}:{$key}:{$locale}";
    }

    /**
     * Gera chave de cache em memória (runtime)
     *
     * @param string|null $tenantId
     * @param string|null $name
     * @param string $key
     * @param string $locale
     * @return string
     */
    protected function getRuntimeCacheKey(?string $tenantId, ?string $name, string $key, string $locale): string
    {
        return $this->getCacheKey($tenantId, $name, $key, $locale);
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
                ->select('name')
                ->distinct()
                ->whereNotNull('name')
                ->pluck('name')
                ->toArray(),
            'runtime_cache_size' => count(self::$runtimeCache),
        ];
    }

    /**
     * Gera arquivo JSON de tradução para um locale específico
     *
     * @param string $locale Locale (ex: 'pt_BR', 'en')
     * @param string|null $tenantId ID do tenant (null para global)
     * @param string|null $outputPath Caminho de saída customizado (opcional)
     * @return string Caminho do arquivo gerado
     */
    public function generateJsonFile(string $locale, ?string $tenantId = null, ?string $outputPath = null): string
    {
        // Busca todas as traduções do locale
        $translations = $this->getAllTranslations($locale, $tenantId);

        // Define o caminho de saída
        if (!$outputPath) {
            $langPath = lang_path();
            $localeFormatted = $locale; // pt_BR -> pt-br

            if ($tenantId) {
                // Para tenant específico: lang/tenants/{tenant_id}/pt-br.json
                $outputPath = "{$langPath}/tenants/{$tenantId}/{$localeFormatted}.json";
            } else {
                // Para global: lang/pt-br.json
                $outputPath = "{$langPath}/{$localeFormatted}.json";
            }
        }

        // Cria o diretório se não existir
        $directory = dirname($outputPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Mescla com traduções existentes do Laravel se for global
        $mergedTranslations = $translations;

        if (!$tenantId && file_exists($outputPath)) {
            $existingTranslations = json_decode(file_get_contents($outputPath), true) ?? [];
            $mergedTranslations = array_merge($existingTranslations, $translations);
        }

        // Ordena as chaves alfabeticamente
        ksort($mergedTranslations);

        // Escreve o arquivo JSON
        file_put_contents(
            $outputPath,
            json_encode($mergedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $outputPath;
    }

    /**
     * Obtém todas as traduções de um locale
     *
     * @param string $locale
     * @param string|null $tenantId
     * @return array Formato: ['chave' => 'tradução']
     */
    public function getAllTranslations(string $locale, ?string $tenantId = null): array
    {
        $groupTable = config('raptor.tables.translation_groups', 'translation_groups');
        $overrideTable = config('raptor.tables.translation_overrides', 'translation_overrides');

        $query = DB::table($overrideTable)
            ->join($groupTable, "{$groupTable}.id", '=', "{$overrideTable}.translation_group_id")
            ->where("{$groupTable}.locale", $locale)
            ->where("{$groupTable}.tenant_id", $tenantId)
            ->select(
                "{$overrideTable}.key",
                "{$overrideTable}.value"
            );

        $results = $query->get();

        $translations = [];

        foreach ($results as $row) {
            // Usa APENAS a key da translation_overrides (sem prefixo do grupo)
            $translations[$row->key] = $row->value;
        }

        return $translations;
    }

    /**
     * Gera arquivos JSON para todos os locales
     *
     * @param string|null $tenantId
     * @return array Lista de arquivos gerados
     */
    public function generateAllJsonFiles(?string $tenantId = null): array
    {
        $groupTable = config('raptor.tables.translation_groups', 'translation_groups');

        // Busca todos os locales disponíveis
        $locales = DB::table($groupTable)
            ->where('tenant_id', $tenantId)
            ->distinct()
            ->pluck('locale')
            ->toArray();

        $generatedFiles = [];

        foreach ($locales as $locale) {
            $generatedFiles[] = $this->generateJsonFile($locale, $tenantId);
        }

        return $generatedFiles;
    }

    /**
     * Importa traduções de um arquivo JSON para o banco de dados
     *
     * @param string $filePath Caminho do arquivo JSON
     * @param string $locale Locale das traduções
     * @param string|null $tenantId ID do tenant (null para global)
     * @param string|null $defaultGroup Grupo padrão para organização (opcional)
     * @return int Número de traduções importadas
     */
    public function importFromJsonFile(string $filePath, string $locale, ?string $tenantId = null, ?string $defaultGroup = null): int
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Arquivo não encontrado: {$filePath}");
        }

        $translations = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Erro ao decodificar JSON: " . json_last_error_msg());
        }

        $count = 0;

        foreach ($translations as $key => $value) {
            // Usa a key diretamente (sem separar por grupo)
            // O grupo serve apenas para organização visual
            $this->setOverride($tenantId, $defaultGroup, $key, $locale, $value);
            $count++;
        }

        return $count;
    }

    /**
     * Sincroniza traduções entre arquivo JSON e banco de dados
     * 
     * @param string $locale
     * @param string|null $tenantId
     * @return array Estatísticas da sincronização
     */
    public function syncJsonWithDatabase(string $locale, ?string $tenantId = null): array
    {
        $localeFormatted = $locale;
        $langPath = lang_path();

        if ($tenantId) {
            $jsonPath = "{$langPath}/tenants/{$tenantId}/{$localeFormatted}.json";
        } else {
            $jsonPath = "{$langPath}/{$localeFormatted}.json";
        }

        $stats = [
            'added' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];

        if (!file_exists($jsonPath)) {
            // Se não existe, gera o arquivo
            $this->generateJsonFile($locale, $tenantId, $jsonPath);
            $stats['added'] = count($this->getAllTranslations($locale, $tenantId));
            return $stats;
        }

        // Carrega traduções do arquivo
        $fileTranslations = json_decode(file_get_contents($jsonPath), true) ?? [];

        // Carrega traduções do banco
        $dbTranslations = $this->getAllTranslations($locale, $tenantId);

        // Atualiza arquivo com novas traduções do banco
        foreach ($dbTranslations as $key => $value) {
            if (!isset($fileTranslations[$key])) {
                $fileTranslations[$key] = $value;
                $stats['added']++;
            } elseif ($fileTranslations[$key] !== $value) {
                $fileTranslations[$key] = $value;
                $stats['updated']++;
            } else {
                $stats['unchanged']++;
            }
        }

        // Salva arquivo atualizado
        ksort($fileTranslations);
        file_put_contents(
            $jsonPath,
            json_encode($fileTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $stats;
    }
}
