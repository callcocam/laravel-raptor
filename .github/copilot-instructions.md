# Laravel Raptor Package - AI Coding Instructions

## Project Overview
This is a Laravel package built with Spatie's laravel-package-tools. It's part of a monorepo under `raptor.1.0/packages/callcocam/`. The package uses Orchestra Testbench for development without a full Laravel installation.

**Key Features:**
- ULID support via `AbstractModel` (uses Laravel's native `HasUlids`)
- Shinobi: Role & Permission system (custom implementation)
- Sluggable: Auto-generate slugs for models
- Optional User model sync for project integration

## Architecture & Structure

### Model Hierarchy
- `AbstractModel` (base model with ULID + Sluggable support)
  - `Models\Auth\User` (authentication with Shinobi permissions)
  - User models should extend `AbstractModel` for ULID support

### Service Provider Pattern
- Main entry: `LaravelRaptorServiceProvider` extends `Spatie\LaravelPackageTools\PackageServiceProvider`
- Package registration uses fluent API: `$package->name()->hasConfigFile()->hasViews()->hasMigration()->hasCommands()`
- Auto-discovery enabled via `composer.json` extra.laravel section

### Namespace Convention
- All classes use `Callcocam\LaravelRaptor` namespace
- Standard header comment block on every file:
  ```php
  /**
   * Created by Claudio Campos.
   * User: callcocam@gmail.com, contato@sigasmart.com.br
   * https://www.sigasmart.com.br
   */
  ```

### Key Directories
- `src/Models/` - Base models (AbstractModel, Auth/User)
- `src/Support/Shinobi/` - Role & Permission system
- `src/Support/Sluggable/` - Slug generation system
- `src/Commands/` - Artisan commands
- `database/migrations/*.stub` - Migration templates (use ULID for primary keys)

## Development Workflow

### Testing with Pest
```bash
composer test              # Run Pest tests
composer test-coverage     # With coverage report
```
- Base test class: `tests/TestCase.php` extends `Orchestra\Testbench\TestCase`
- All tests automatically use TestCase via `uses(TestCase::class)->in(__DIR__)` in `tests/Pest.php`
- Factory namespace resolution configured in TestCase::setUp()

### Code Quality
```bash
composer format     # Laravel Pint (code style)
composer analyse    # PHPStan level 5
```
- PHPStan config: `phpstan.neon.dist` with Octane compatibility checks
- Architecture tests: `tests/ArchTest.php` prevents debugging functions (dd, dump, ray)

### Workbench Development Environment
```bash
composer build      # Build workbench
composer start      # Serve workbench at http://127.0.0.1:8000
```
- Testbench provides isolated Laravel environment without full installation
- Workbench namespace: `Workbench\App\` (separate from package namespace)
- Commands: `vendor/bin/testbench` wrapper for artisan-like commands

## Package Installation & Sync

### Install Command
```bash
php artisan laravel-raptor:install
```
- Publishes config, migrations, and assets
- Automatically calls `laravel-raptor:sync --force` to adapt user project

### Sync Command
```bash
php artisan laravel-raptor:sync [--force]
```
- Adapts existing Laravel projects to use Raptor models
- Updates `app/Models/User.php` to extend `AbstractModel`
- Converts User migration from `$table->id()` to `$table->ulid('id')->primary()`
- Creates `.backup` files before making changes
- Use `--force` to skip confirmation prompts

## Patterns & Conventions

### ULID Support
- Use Laravel's native `HasUlids` trait (already included in `AbstractModel`)
- Migrations use `$table->ulid('id')->primary()` instead of `$table->id()`
- Models extending `AbstractModel` automatically use ULID

### Sluggable Pattern
- `AbstractModel` includes `HasSlug` trait
- Implement `slugFrom()` and `slugTo()` methods in models:
  ```php
  protected function slugFrom(): string { return 'name'; }
  protected function slugTo(): string { return 'slug'; }
  ```

### Shinobi (Permissions & Roles)
- User model includes `HasRolesAndPermissions` trait
- Available methods: `assignRole()`, `givePermissionTo()`, `hasRole()`, `hasPermission()`
- Middleware: `UserHasRole`, `UserHasAnyRole`, `UserHasAllRoles`
- Models: `Support\Shinobi\Models\Role`, `Support\Shinobi\Models\Permission`

### Commands
- Extend `Illuminate\Console\Command`
- Registered via `->hasCommands([])` in service provider
- Success return: `self::SUCCESS` constant

### Migrations
- Stub files in `database/migrations/*.stub` (not `.php`)
- Published via `hasMigration('create_table_name')` - strips "create_" prefix
- Use anonymous class pattern with `up()` method only
- Always use ULID for primary keys

### Configuration
- Single config file: `config/raptor.php`
- Published via `php artisan vendor:publish --tag="laravel-raptor-config"`

## Dependencies
- **Required**: PHP ^8.2, Laravel ^12.0, Spatie laravel-package-tools ^1.16
- **Dev**: Pest ^2.34, Orchestra Testbench ^9.0||^8.22, Pint ^1.18, PHPStan
- PSR-4 autoloading for both main and dev namespaces

## Common Tasks

### Creating Models with ULID
```php
use Callcocam\LaravelRaptor\Models\AbstractModel;

class Post extends AbstractModel
{
    protected function slugFrom(): string { return 'title'; }
    protected function slugTo(): string { return 'slug'; }
}
```

### Adding New Commands
1. Create in `src/Commands/` extending `Illuminate\Console\Command`
2. Register in `LaravelRaptorServiceProvider::configurePackage()` via `->hasCommands([YourCommand::class])`

### Adding Migrations
1. Create `.stub` file in `database/migrations/` with ULID primary key
2. Use `hasMigration('your_migration_name')` in service provider (without `.php.stub`)

### Running Package in Development
Use testbench commands instead of standard artisan:
- `vendor/bin/testbench migrate`
- `vendor/bin/testbench make:model`
- `vendor/bin/testbench serve` (via `composer start`)

