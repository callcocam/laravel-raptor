<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Callcocam\LaravelRaptor\Services\TranslationService;
use Illuminate\Console\Command;

class TranslationSyncCommand extends Command
{
    protected $signature = 'raptor:translation:sync
                            {--locale= : Sync specific locale}
                            {--tenant= : Sync for specific tenant ID}
                            {--all : Sync all locales}';

    protected $description = 'Sync JSON translation files with database';

    public function handle(TranslationService $translationService): int
    {
        $locale = $this->option('locale');
        $tenantId = $this->option('tenant');
        $all = $this->option('all');

        try {
            if ($all) {
                // Sincroniza todos os locales
                $this->info('Syncing all locales...');
                $locales = ['pt_BR', 'en', 'es', 'fr'];
                
                $totalStats = [
                    'added' => 0,
                    'updated' => 0,
                    'unchanged' => 0,
                ];

                foreach ($locales as $loc) {
                    $stats = $translationService->syncJsonWithDatabase($loc, $tenantId);
                    
                    $totalStats['added'] += $stats['added'];
                    $totalStats['updated'] += $stats['updated'];
                    $totalStats['unchanged'] += $stats['unchanged'];
                    
                    $this->line("   {$loc}: +{$stats['added']} ~{$stats['updated']} ={$stats['unchanged']}");
                }

                $this->info('✅ Sync completed!');
                $this->line("   Total Added: {$totalStats['added']}");
                $this->line("   Total Updated: {$totalStats['updated']}");
                $this->line("   Total Unchanged: {$totalStats['unchanged']}");
                
                return self::SUCCESS;
            }

            if (!$locale) {
                $this->error('Please specify --locale or use --all');
                return self::FAILURE;
            }

            // Sincroniza um locale específico
            $this->info("Syncing locale: {$locale}");
            $stats = $translationService->syncJsonWithDatabase($locale, $tenantId);
            
            $this->info('✅ Sync completed!');
            $this->line("   Added: {$stats['added']}");
            $this->line("   Updated: {$stats['updated']}");
            $this->line("   Unchanged: {$stats['unchanged']}");
            
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
