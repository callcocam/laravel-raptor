<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Translation;

use Callcocam\LaravelRaptor\Services\TranslationService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;

/**
 * Service Provider responsável pelo sistema de traduções customizadas por tenant
 *
 * Intercepta o sistema de tradução do Laravel para aplicar overrides do banco de dados
 * com prioridade: Tenant > Global DB > Laravel Lang Files
 *
 * @package Callcocam\LaravelRaptor\Support\Translation
 * @author Claudio Campos <callcocam@gmail.com>
 */
class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap do service provider
     */
    public function boot(): void
    {
        if (!config('raptor.translation.enabled', true)) {
            return;
        }

        $this->extendTranslator();
    }

    /**
     * Registra os serviços no container
     */
    public function register(): void
    {
        // Registra o TranslationService como singleton
        $this->app->singleton(TranslationService::class);

        // Registra alias para facilitar acesso
        $this->app->alias(TranslationService::class, 'translation.service');
    }

    /**
     * Estende o Translator do Laravel para interceptar traduções
     */
    protected function extendTranslator(): void
    {
        // Macro para resolver traduções com override
        Translator::macro('getWithOverride', function (string $key, array $replace = [], ?string $locale = null) {
            /** @var Translator $this */
            $locale = $locale ?: $this->locale();

            // Separa group e key
            [$group, $item] = $this->parseKey($key);

            // Obtém tenant atual
            $tenantId = app('tenant')?->id ?? null;

            // Busca override no banco de dados
            $translationService = app(TranslationService::class);
            $override = $translationService->get($tenantId, $group, $item, $locale);

            // Se encontrou override, usa ele
            if ($override !== null) {
                return $this->makeReplacements($override, $replace);
            }

            // Caso contrário, usa comportamento padrão do Laravel
            return $this->get($key, $replace, $locale);
        });

        // Hook para interceptar o método get() padrão do Translator
        // Somente se estiver habilitado nas configurações
        if (config('raptor.translation.intercept_default_get', true)) {
            $this->interceptDefaultGet();
        }
    }

    /**
     * Intercepta o método get() padrão do Translator
     *
     * IMPORTANTE: Esta é uma abordagem avançada que requer cuidado.
     * Estamos usando um hack do Laravel para interceptar chamadas ao translator.
     */
    protected function interceptDefaultGet(): void
    {
        $this->app->extend('translator', function (Translator $translator, $app) {
            return new class($translator, $app) extends Translator
            {
                private Translator $originalTranslator;
                private $app;

                public function __construct(Translator $translator, $app)
                {
                    $this->originalTranslator = $translator;
                    $this->app = $app;

                    // Passa configurações do translator original
                    parent::__construct(
                        $translator->getLoader(),
                        $translator->locale()
                    );

                    $this->setFallback($translator->getFallback());
                }

                /**
                 * Override do método get para aplicar traduções customizadas
                 */
                public function get($key, array $replace = [], $locale = null)
                {
                    $locale = $locale ?: $this->locale();

                    // Separa group e key
                    [$group, $item] = $this->parseKey($key);

                    // Ignora traduções de validação e outros sistemas core do Laravel
                    // para evitar quebrar funcionalidades essenciais
                    $ignoredGroups = config('raptor.translation.ignored_groups', [
                        'validation',
                        'passwords',
                        'pagination',
                    ]);

                    if (in_array($group, $ignoredGroups, true)) {
                        return parent::get($key, $replace, $locale);
                    }

                    // Obtém tenant atual
                    $tenantId = null;
                    try {
                        $tenant = $this->app->make('tenant');
                        $tenantId = $tenant?->id;
                    } catch (\Exception $e) {
                        // Tenant não disponível (modo landlord ou site público)
                    }

                    // Busca override no banco de dados
                    try {
                        $translationService = $this->app->make(TranslationService::class);
                        $override = $translationService->get($tenantId, $group, $item, $locale);

                        // Se encontrou override, usa ele
                        if ($override !== null) {
                            return $this->makeReplacements($override, $replace);
                        }
                    } catch (\Exception $e) {
                        // Em caso de erro (ex: tabela ainda não existe), usa comportamento padrão
                        // Isso permite que a aplicação funcione durante migrations
                    }

                    // Fallback: usa comportamento padrão do Laravel
                    return parent::get($key, $replace, $locale);
                }

                /**
                 * Métodos auxiliares delegados ao translator original
                 */
                public function choice($key, $number = 1, array $replace = [], $locale = null)
                {
                    return parent::choice($key, $number, $replace, $locale);
                }

                public function setLocale($locale)
                {
                    parent::setLocale($locale);
                    $this->originalTranslator->setLocale($locale);
                }
            };
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            TranslationService::class,
            'translation.service',
        ];
    }
}
