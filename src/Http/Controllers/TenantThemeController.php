<?php

namespace Callcocam\LaravelRaptor\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\LaravelRaptor\Http\Requests\Admin\UpdateTenantThemeRequest;
use Callcocam\LaravelRaptor\Http\Resources\TenantResource;
use Callcocam\LaravelRaptor\Models\Tenant;

class TenantThemeController extends Controller
{

    /**
     * Update the tenant's theme settings.
     */
    public function update(UpdateTenantThemeRequest $request, Tenant $tenant)
    {
     
        $settings = $tenant->settings ?? [];
        $settings['theme'] = $request->validated();

        $tenant->update(['settings' => $settings]);

        return redirect()->back()->with('success', 'Tema atualizado com sucesso.');
    }
}
