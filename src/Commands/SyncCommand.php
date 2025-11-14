<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncCommand extends Command
{
    public $signature = 'laravel-raptor:sync {--force : Force the operation without confirmation}';

    public $description = 'Sync Laravel Raptor with your application (adapt User model and migrations)';

    public function handle(): int
    {
        $this->info('🚀 Starting Laravel Raptor sync...');

        if (!$this->option('force') && !$this->confirm('This will modify your User model and migrations. Continue?', false)) {
            $this->warn('Sync cancelled.');
            return self::FAILURE;
        }

        $this->createBackupDirectory();
        $this->syncUserModel();
        $this->backupAndUpdateUserMigration();

        $this->newLine();
        $this->info('✅ Laravel Raptor sync completed successfully!');
        $this->newLine();
        $this->comment('Backups saved in: storage/raptor-backups/' . date('Y-m-d_His'));
        $this->newLine();
        $this->comment('Next steps:');
        $this->comment('  1. Review the changes in app/Models/User.php');
        $this->comment('  2. Check database/migrations/*_create_users_table.php');
        $this->comment('  3. Run: php artisan migrate');

        return self::SUCCESS;
    }

    protected function createBackupDirectory(): void
    {
        $backupPath = storage_path('raptor-backups/' . date('Y-m-d_His'));
        
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $this->info('📦 Creating backups in: ' . $backupPath);

        // Backup User model
        $userModelPath = app_path('Models/User.php');
        if (File::exists($userModelPath)) {
            File::copy($userModelPath, $backupPath . '/User.php');
            $this->comment('   ✓ User.php backed up');
        }

        // Backup migrations folder
        $migrationsPath = database_path('migrations');
        if (File::exists($migrationsPath)) {
            File::copyDirectory($migrationsPath, $backupPath . '/migrations');
            $this->comment('   ✓ Migrations folder backed up');
        }

        $this->newLine();
    }

    protected function syncUserModel(): void
    {
        $userModelPath = app_path('Models/User.php');

        if (!File::exists($userModelPath)) {
            $this->warn('User model not found at ' . $userModelPath);
            return;
        }

        $this->info('📝 Updating User model...');

        $content = File::get($userModelPath);

        // Check if already extends Auth\User from package
        if (str_contains($content, 'use Callcocam\LaravelRaptor\Models\Auth\User') || str_contains($content, 'extends User')) {
            $this->comment('   User model already extends Raptor Auth\User');
            return;
        }

        // Replace extends Authenticatable with extends User from package
        $updated = preg_replace(
            '/use Illuminate\\\\Foundation\\\\Auth\\\\User as Authenticatable;/',
            "use Callcocam\\LaravelRaptor\\Models\\Auth\\User as RaptorUser;",
            $content
        );

        $updated = preg_replace(
            '/class User extends Authenticatable/',
            "class User extends RaptorUser",
            $updated
        );

        // Remove unnecessary imports and traits that are already in RaptorUser
        $unnecessaryImports = [
            'use Illuminate\Contracts\Auth\MustVerifyEmail;',
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use Illuminate\Foundation\Auth\User as Authenticatable;',
            'use Illuminate\Notifications\Notifiable;',
        ];

        foreach ($unnecessaryImports as $import) {
            $updated = str_replace($import, '', $updated);
        }

        // Remove unnecessary traits
        $updated = preg_replace('/use HasFactory,?\s*/', 'use ', $updated);
        $updated = preg_replace('/use\s*,\s*Notifiable;/', 'use Notifiable;', $updated);
        
        // Clean up multiple empty lines
        $updated = preg_replace('/\n{3,}/', "\n\n", $updated);

        File::put($userModelPath, $updated);
        $this->info('   ✓ User model updated successfully');
    }

    protected function backupAndUpdateUserMigration(): void
    {
        $migrationsPath = database_path('migrations');
        $userMigrations = File::glob($migrationsPath . '/*_create_users_table.php');

        if (empty($userMigrations)) {
            $this->warn('Users migration not found');
            return;
        }

        $migrationPath = $userMigrations[0];
        $this->info('📝 Updating users migration...');

        $content = File::get($migrationPath);

        // Replace $table->id() with $table->ulid('id')->primary()
        $updated = preg_replace(
            '/\$table->id\(\);/',
            "\$table->ulid('id')->primary();",
            $content
        );

        // Check if already uses ulid
        if ($content === $updated) {
            $this->comment('   Migration already uses ULID or has custom ID');
            return;
        }

        File::put($migrationPath, $updated);
        $this->info('   ✓ Users migration updated to use ULID');
    }
}
