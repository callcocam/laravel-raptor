<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Domain Router
|--------------------------------------------------------------------------
|
| Este arquivo gerencia as rotas baseadas em domínio/subdomínio:
| 1. Domínio principal (site da aplicação)
| 2. Subdomínio 'landlord' (gerenciamento da aplicação e tenants)
| 3. Subdomínios de tenants (clientes)
|
*/

// Detecta o ambiente e configuração
$landlordSubdomain = config('raptor.landlord.subdomain', 'landlord');
$mainDomain = config('raptor.main_domain', config('app.url') ? parse_url(config('app.url'), PHP_URL_HOST) : 'localhost');

/*
|--------------------------------------------------------------------------
| 1. DOMÍNIO PRINCIPAL - Site da Aplicação
|--------------------------------------------------------------------------
| Rota para o domínio principal sem subdomínio
| Exemplo: example.com
|
*/
Route::domain($mainDomain)->group(function () {
    // Rotas públicas do site principal
    Route::get('/', function () {
        return inertia('welcome');
    })->name('home');

    // Inclui rotas públicas do site se existirem
    if (file_exists(__DIR__ . '/site.php')) {
        require __DIR__ . '/site.php';
    }
});

/*
|--------------------------------------------------------------------------
| 2. LANDLORD - Gerenciamento da Aplicação
|--------------------------------------------------------------------------
| Rota para o subdomínio de gerenciamento
| Exemplo: landlord.example.com
|
*/
Route::domain("{$landlordSubdomain}.{$mainDomain}")->group(function () {
    // Middleware de autenticação e permissões de landlord
    Route::middleware(['web', 'auth', 'landlord'])->group(function () {
        
        // Dashboard do Landlord
        Route::get('/dashboard', function () {
            return inertia('landlord/dashboard');
        })->name('landlord.dashboard');

        // Inclui rotas do landlord se existirem
        if (file_exists(__DIR__ . '/landlord.php')) {
            require __DIR__ . '/landlord.php';
        }
    });
});

/*
|--------------------------------------------------------------------------
| 3. TENANTS - Subdomínios de Clientes
|--------------------------------------------------------------------------
| Rota para qualquer outro subdomínio que não seja 'landlord' ou 'www'
| Exemplos: cliente1.example.com, empresa.example.com
| Também suporta domínios customizados configurados por tenant
|
*/
Route::domain('{tenant}.' . $mainDomain)
    ->where('tenant', '^(?!' . $landlordSubdomain . '|www).*$') // Exclui 'landlord' e 'www'
    ->middleware(['web', 'tenant'])
    ->group(function () {
        
        // Dashboard do Tenant
        Route::get('/dashboard', function () {
            return inertia('tenant/dashboard');
        })->middleware('auth')->name('tenant.dashboard');

        // Inclui rotas do tenant se existirem
        if (file_exists(__DIR__ . '/tenant.php')) {
            require __DIR__ . '/tenant.php';
        }
    });

/*
|--------------------------------------------------------------------------
| DOMÍNIOS CUSTOMIZADOS DE TENANTS
|--------------------------------------------------------------------------
| Para tenants que possuem domínio próprio configurado
| Exemplo: empresaxyz.com.br apontando para um tenant específico
|
*/
if (config('raptor.enable_custom_domains', false)) {
    // Esta rota será processada pelo middleware tenant que identificará
    // o tenant pelo domínio customizado cadastrado no banco
    Route::middleware(['web', 'tenant.custom.domain'])->group(function () {
        
        Route::get('/dashboard', function () {
            return inertia('tenant/dashboard');
        })->middleware('auth')->name('custom.tenant.dashboard');

        // Inclui rotas do tenant se existirem
        if (file_exists(__DIR__ . '/tenant.php')) {
            require __DIR__ . '/tenant.php';
        }
    });
}
