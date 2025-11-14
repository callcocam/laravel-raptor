<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Callcocam\LaravelRaptor\Commands\LaravelRaptorCommand;
use Callcocam\LaravelRaptor\Commands\SyncCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;

class LaravelRaptorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-raptor')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_raptor_table')
            ->hasCommands([
                LaravelRaptorCommand::class,
                SyncCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                // Customize the install command created by the package tools
                $command->startWith(function (InstallCommand $command) {
                    $command->call('laravel-raptor:sync', [
                        '--force' => true,
                    ]);
                });
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->publishAssets();
                // Optional: You can customize the install command here
            });
    }
}
