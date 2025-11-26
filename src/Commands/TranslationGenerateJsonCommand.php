<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Callcocam\LaravelRaptor\Services\TranslationService;
use Illuminate\Console\Command;

class TranslationGenerateJsonCommand extends Command
{
    protected $signature = 'raptor:translation:generate-json
                            {--locale= : Generate JSON for specific locale}
                            {--tenant= : Generate JSON for specific tenant ID}
                            {--all : Generate JSON for all locales}';

    protected $description = 'Generate JSON translation files from database';

    public function handle(TranslationService $translationService): int
    {
        $locale = $this->option('locale');
        $tenantId = $this->option('tenant');
        $all = $this->option('all');

        try {
            if ($all) {
                // Gera para todos os locales
                $this->info('Generating JSON files for all locales...');
                $files = $translationService->generateAllJsonFiles($tenantId);
                
                $this->info('✅ Generated ' . count($files) . ' files:');
                foreach ($files as $file) {
                    $this->line("   - {$file}");
                }
                
                return self::SUCCESS;
            }

            if (!$locale) {
                $this->error('Please specify --locale or use --all');
                return self::FAILURE;
            }

            // Gera para um locale específico
            $this->info("Generating JSON file for locale: {$locale}");
            $filePath = $translationService->generateJsonFile($locale, $tenantId);
            
            $this->info("✅ JSON file generated: {$filePath}");
            
            // Mostra estatísticas
            $translations = $translationService->getAllTranslations($locale, $tenantId);
            $this->line("   Translations: " . count($translations));
            
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
