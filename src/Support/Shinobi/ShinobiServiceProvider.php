<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Support\Shinobi;

use Exception;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ShinobiServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return null
     */
    public function boot()
    {
        // $this->mergeConfigFrom(__DIR__.'/../../../config/shinobi.php', 'shinobi');

        $this->registerGates();
        $this->registerBladeDirectives();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton('shinobi', function ($app) {
            $auth = $app->make('Illuminate\Contracts\Auth\Guard');

            return new \Callcocam\LaravelRaptor\Support\Shinobi\Shinobi($auth);
        });
    }

    /**
     * Register the permission gates.
     *
     * @return void
     */
    protected function registerGates()
    {
        Gate::before(function (Authorizable $user, string $permission) {
            try {
                if (method_exists($user, 'hasPermissionTo')) {
                    return $user->hasPermissionTo($permission) ?: null;
                }
            } catch (Exception $e) {
                //
                // dd($e);
                Log::error("Error checking permission [$permission] for user ID {$user->getAuthIdentifier()}: ".$e->getMessage());
            }
        });
    }

    /**
     * Register the blade directives.
     *
     * @return void
     */
    protected function registerBladeDirectives()
    {
        Blade::if('role', function ($role) {
            return auth()->user() and auth()->user()->hasRole($role);
        });

        Blade::if('anyrole', function (...$roles) {
            return auth()->user() and auth()->user()->hasAnyRole(...$roles);
        });

        Blade::if('allroles', function (...$roles) {
            return auth()->user() and auth()->user()->hasAllRoles(...$roles);
        });
    }
}
