<?php

namespace Callcocam\LaravelRaptor\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\LaravelRaptor\Http\Requests\Admin\UpdateTenantThemeRequest;
use Callcocam\LaravelRaptor\Models\Tenant;

class TenantThemeController extends Controller
{
    /**
     * Update the tenant's theme settings.
     */
    public function update(UpdateTenantThemeRequest $request)
    {

        $tenant = Tenant::query()->where('id', tenant_id())->firstOrFail();
        $settings = $tenant->settings ?? [];
        $settings['theme'] = $request->validated();

        $tenant->update(['settings' => $settings]);

        return redirect()->back()->with('success', 'Tema atualizado com sucesso.');
    }
}
