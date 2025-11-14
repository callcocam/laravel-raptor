# Laravel Raptor Package - AI Coding Instructions

## Project Overview
This is a Laravel package built with Spatie's laravel-package-tools. It's part of a monorepo under `raptor.1.0/packages/callcocam/`. The package uses Orchestra Testbench for development without a full Laravel installation.

## Architecture & Structure

### Service Provider Pattern
- Main entry: `LaravelRaptorServiceProvider` extends `Spatie\LaravelPackageTools\PackageServiceProvider`
- Package registration uses fluent API: `$package->name()->hasConfigFile()->hasViews()->hasMigration()->hasCommand()`
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

### Key Files
- `src/LaravelRaptorServiceProvider.php` - Package bootstrap
- `src/LaravelRaptor.php` - Main package class (currently empty scaffold)
- `config/raptor.php` - Configuration (currently empty)
- `database/migrations/*.stub` - Migration templates (note: naming uses `laravel_raptor_table` not `raptor_table`)

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

## Patterns & Conventions

### Facades
- Pattern: `Facades\LaravelRaptor` facade → `LaravelRaptor` class
- Uses `getFacadeAccessor()` returning FQCN, not container binding

### Commands
- Extend `Illuminate\Console\Command`
- Registered via `hasCommand()` in service provider
- Success return: `self::SUCCESS` constant

### Migrations
- Stub files in `database/migrations/*.stub` (not `.php`)
- Published via `hasMigration('create_laravel_raptor_table')` - strips "create_" prefix
- Use anonymous class pattern with `up()` method only

### Configuration
- Single config file: `config/raptor.php`
- Published via `php artisan vendor:publish --tag="laravel-raptor-config"`

## Dependencies
- **Required**: PHP ^8.2, Laravel ^12.0, Spatie laravel-package-tools ^1.16
- **Dev**: Pest ^2.34, Orchestra Testbench ^9.0||^8.22, Pint ^1.18, PHPStan
- PSR-4 autoloading for both main and dev namespaces

## Common Tasks

### Adding a New Command
1. Create in `src/Commands/` extending `Illuminate\Console\Command`
2. Register in `LaravelRaptorServiceProvider::configurePackage()` via `->hasCommand(YourCommand::class)`

### Adding Migration
1. Create `.stub` file in `database/migrations/`
2. Use `hasMigration('your_migration_name')` in service provider (without `.php.stub`)

### Running Package in Development
Use testbench commands instead of standard artisan:
- `vendor/bin/testbench migrate`
- `vendor/bin/testbench make:model`
- `vendor/bin/testbench serve` (via `composer start`)
