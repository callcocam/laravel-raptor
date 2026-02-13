<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use Callcocam\LaravelRaptor\Http\Controllers\ResourceController;
use Callcocam\LaravelRaptor\Support\Pages\Index;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página de gerenciamento DNS Cloudflare.
 * O componente e o botão Voltar são definidos na página Vue (admin/cloudflare/create.vue).
 * API em Api\CloudflareController.
 */
class CloudflareController extends ResourceController
{

    public function getPages(): array
    {
        return [
            'index' => Index::route('/cloudflare')
                ->label('Cloudflare DNS')
                ->name('cloudflare.index')
                ->middlewares(['auth', 'verified']),
        ];
    }
    /**
     * Exibe a página Cloudflare DNS (configuração do componente na própria página).
     */
    public function index(Request $request): Response
    {
        $dashboardRoute = config('raptor.controllers.dashboard.index.name', 'dashboard');

        return Inertia::render('admin/cloudflare/create', [
            'title' => __('Cloudflare DNS'),
            'message' => __('Gerencie registros DNS: crie ou remova domínios e subdomínios nas suas zones.'),
            'breadcrumbs' => [
                ['label' => __('Dashboard'), 'url' => route($dashboardRoute)],
                ['label' => __('Cloudflare DNS'), 'url' => null],
            ],
            'backUrl' => route($dashboardRoute),
            'apiBaseUrl' => '/cloudflare',
        ]);
    }

    protected function resourcePath(): ?string
    {
        return 'landlord';
    }
}
