<?php

namespace Callcocam\LaravelRaptor\Http\Controllers\Api;

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
        $this->authorize('update', $tenant);

        $settings = $tenant->settings ?? [];
        $settings['theme'] = $request->validated();

        $tenant->update(['settings' => $settings]);

        return new TenantResource($tenant->fresh());
    }
}
