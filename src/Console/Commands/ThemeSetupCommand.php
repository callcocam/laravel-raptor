<?php

namespace Callcocam\LaravelRaptor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ThemeSetupCommand extends Command
{
    protected $signature = 'raptor:theme-setup';

    protected $description = 'Setup theme system by publishing assets and updating configuration';

    protected Filesystem $files;

    protected string $packagePath;

    protected string $projectPath;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->packagePath = dirname(__DIR__, 3);
        $this->projectPath = base_path();
    }

    public function handle(): int
    {
        $this->info('Setting up theme system...');
        $this->newLine();

        $this->publishCssFiles();
        $this->publishJsFiles();
        $this->updateMainCss();
        $this->updateAppTs();

        $this->newLine();
        $this->info('✓ Theme system setup completed successfully!');
        $this->info('Next steps:');
        $this->line('  1. Run "npm run build" to compile assets');
        $this->line('  2. Import ThemeSwitcher component where needed');
        $this->line('  3. Visit your app to see the theme system in action');

        return self::SUCCESS;
    }

    protected function publishCssFiles(): void
    {
        $this->info('Publishing CSS files...');

        // Create directories
        $cssDir = "{$this->projectPath}/resources/css";
        $colorsDir = "{$cssDir}/colors";

        $this->files->ensureDirectoryExists($colorsDir);

        // Copy themes.css
        $source = "{$this->packagePath}/resources/css/themes.css";
        $destination = "{$cssDir}/themes.css";

        if ($this->files->exists($source)) {
            $this->files->copy($source, $destination);
            $this->line('  ✓ Copied themes.css');
        }

        // Copy colors directory
        $sourceColorsDir = "{$this->packagePath}/resources/css/colors";

        if ($this->files->isDirectory($sourceColorsDir)) {
            $colorFiles = $this->files->files($sourceColorsDir);

            foreach ($colorFiles as $file) {
                $filename = $file->getFilename();
                $this->files->copy(
                    $file->getPathname(),
                    "{$colorsDir}/{$filename}"
                );
            }

            $this->line('  ✓ Copied ' . count($colorFiles) . ' color files');
        }
    }

    protected function publishJsFiles(): void
    {
        $this->info('Publishing JavaScript files...');

        // Create directories
        $composablesDir = "{$this->projectPath}/resources/js/composables";
        $themeComponentsDir = "{$this->projectPath}/resources/js/components/theme";

        $this->files->ensureDirectoryExists($composablesDir);
        $this->files->ensureDirectoryExists($themeComponentsDir);

        // Copy useTheme.ts
        $source = "{$this->packagePath}/resources/js/composables/useTheme.ts";
        $destination = "{$composablesDir}/useTheme.ts";

        if ($this->files->exists($source)) {
            $this->files->copy($source, $destination);
            $this->line('  ✓ Copied useTheme.ts');
        }

        // Copy ThemeSwitcher.vue
        $source = "{$this->packagePath}/resources/js/components/theme/ThemeSwitcher.vue";
        $destination = "{$themeComponentsDir}/ThemeSwitcher.vue";

        if ($this->files->exists($source)) {
            $this->files->copy($source, $destination);
            $this->line('  ✓ Copied ThemeSwitcher.vue');
        }
    }

    protected function updateMainCss(): void
    {
        $this->info('Updating main.css...');

        $mainCssPath = "{$this->projectPath}/resources/css/main.css";

        if (! $this->files->exists($mainCssPath)) {
            $this->warn('  ⚠ main.css not found, skipping');
            return;
        }

        $content = $this->files->get($mainCssPath);

        // Check if imports already exist
        $hasColorsImport = str_contains($content, '@import "./colors/index.css"');
        $hasThemesImport = str_contains($content, '@import "./themes.css"');

        if ($hasColorsImport && $hasThemesImport) {
            $this->line('  ✓ Already configured');
            return;
        }

        // Find the position after fonts.css import
        $lines = explode("\n", $content);
        $insertPosition = 0;

        foreach ($lines as $index => $line) {
            if (str_contains($line, '@import "./fonts.css"')) {
                $insertPosition = $index + 1;
                break;
            }
        }

        // Insert imports after fonts.css
        $importsToAdd = [];

        if (! $hasColorsImport) {
            $importsToAdd[] = '@import "./colors/index.css";';
        }

        if (! $hasThemesImport) {
            $importsToAdd[] = '@import "./themes.css";';
        }

        if (! empty($importsToAdd)) {
            array_splice($lines, $insertPosition, 0, $importsToAdd);
            $this->files->put($mainCssPath, implode("\n", $lines));
            $this->line('  ✓ Added theme imports');
        }
    }

    protected function updateAppTs(): void
    {
        $this->info('Updating app.ts...');

        $appTsPath = "{$this->projectPath}/resources/js/app.ts";

        if (! $this->files->exists($appTsPath)) {
            $this->warn('  ⚠ app.ts not found, skipping');
            return;
        }

        $content = $this->files->get($appTsPath);

        // Check if already configured
        if (str_contains($content, 'initializeThemeSystem')) {
            $this->line('  ✓ Already configured');
            return;
        }

        // Add import at the top with other imports
        $importLine = "import { initializeThemeSystem } from './composables/useTheme';";

        // Find position after last import
        $lines = explode("\n", $content);
        $lastImportPosition = 0;

        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'import ')) {
                $lastImportPosition = $index;
            }
        }

        // Insert import after last import
        array_splice($lines, $lastImportPosition + 1, 0, [$importLine]);

        // Add initialization call at the end
        $lines[] = '';
        $lines[] = '// Initialize theme system (colors, fonts, rounded, variants)';
        $lines[] = 'initializeThemeSystem();';

        $this->files->put($appTsPath, implode("\n", $lines));

        $this->line('  ✓ Added theme initialization');
    }
}
