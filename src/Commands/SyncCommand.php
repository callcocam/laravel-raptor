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

        $this->syncUserModel();
        $this->backupAndUpdateUserMigration();

        $this->newLine();
        $this->info('✅ Laravel Raptor sync completed successfully!');
        $this->newLine();
        $this->comment('Next steps:');
        $this->comment('  1. Review the changes in app/Models/User.php');
        $this->comment('  2. Check database/migrations/*_create_users_table.php');
        $this->comment('  3. Run: php artisan migrate');

        return self::SUCCESS;
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

        // Backup original
        $backupPath = $userModelPath . '.backup';
        File::put($backupPath, $content);
        $this->comment('   Backup created: ' . $backupPath);

        // Check if already extends AbstractModel
        if (str_contains($content, 'extends AbstractModel')) {
            $this->comment('   User model already extends AbstractModel');
            return;
        }

        // Replace extends Authenticatable with extends AbstractModel
        $updated = preg_replace(
            '/use Illuminate\\\\Foundation\\\\Auth\\\\User as Authenticatable;/',
            "use Callcocam\\LaravelRaptor\\Models\\AbstractModel;\nuse Illuminate\\Auth\\Authenticatable;\nuse Illuminate\\Auth\\MustVerifyEmail;\nuse Illuminate\\Auth\\Passwords\\CanResetPassword;\nuse Illuminate\\Contracts\\Auth\\Access\\Authorizable as AuthorizableContract;\nuse Illuminate\\Contracts\\Auth\\Authenticatable as AuthenticatableContract;\nuse Illuminate\\Contracts\\Auth\\CanResetPassword as CanResetPasswordContract;\nuse Illuminate\\Foundation\\Auth\\Access\\Authorizable;",
            $content
        );

        $updated = preg_replace(
            '/class User extends Authenticatable/',
            "class User extends AbstractModel implements\n    AuthenticatableContract,\n    AuthorizableContract,\n    CanResetPasswordContract",
            $updated
        );

        // Add traits if not present
        if (!str_contains($updated, 'use Authenticatable')) {
            $updated = preg_replace(
                '/(class User extends AbstractModel.*?\{)/',
                "$1\n    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;",
                $updated
            );
        }

        // Remove HasFactory if present (AbstractModel handles this)
        $updated = str_replace('use HasFactory;', '', $updated);
        $updated = str_replace('use Illuminate\Database\Eloquent\Factories\HasFactory;', '', $updated);

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

        // Backup original
        $backupPath = $migrationPath . '.backup';
        File::put($backupPath, $content);
        $this->comment('   Backup created: ' . $backupPath);

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
