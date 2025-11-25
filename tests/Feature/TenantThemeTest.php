<?php

use Callcocam\LaravelRaptor\Models\Auth\User;
use Callcocam\LaravelRaptor\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create([
        'settings' => ['existing' => 'value'],
    ]);

    $this->user = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    // Grant the user permission to update tenants
    $permission = \Callcocam\LaravelRaptor\Models\Permission::firstOrCreate([
        'slug' => 'landlord.tenants.update',
        'name' => 'Update Tenants',
    ]);

    $this->user->permissions()->attach($permission);
});

it('can update tenant theme', function () {
    $themeData = [
        'color' => 'blue',
        'font' => 'inter',
        'rounded' => 'large',
        'variant' => 'default',
    ];

    $response = $this->actingAs($this->user)
        ->patchJson("/landlord/tenants/{$this->tenant->id}/theme", $themeData);

    $response->assertSuccessful();

    $this->tenant->refresh();

    expect($this->tenant->settings['theme'])->toBe($themeData);
    expect($this->tenant->settings['existing'])->toBe('value');
});

it('validates theme color values', function () {
    $response = $this->actingAs($this->user)
        ->patchJson("/landlord/tenants/{$this->tenant->id}/theme", [
            'color' => 'invalid-color',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['color']);
});

it('validates theme font values', function () {
    $response = $this->actingAs($this->user)
        ->patchJson("/landlord/tenants/{$this->tenant->id}/theme", [
            'font' => 'invalid-font',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['font']);
});

it('validates theme rounded values', function () {
    $response = $this->actingAs($this->user)
        ->patchJson("/landlord/tenants/{$this->tenant->id}/theme", [
            'rounded' => 'invalid-rounded',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['rounded']);
});

it('validates theme variant values', function () {
    $response = $this->actingAs($this->user)
        ->patchJson("/landlord/tenants/{$this->tenant->id}/theme", [
            'variant' => 'invalid-variant',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['variant']);
});

it('requires authentication', function () {
    $response = $this->patchJson("/landlord/tenants/{$this->tenant->id}/theme", [
        'color' => 'blue',
    ]);

    $response->assertUnauthorized();
});

it('requires tenants.update permission', function () {
    $userWithoutPermission = User::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $response = $this->actingAs($userWithoutPermission)
        ->patchJson("/landlord/tenants/{$this->tenant->id}/theme", [
            'color' => 'blue',
        ]);

    $response->assertForbidden();
});

it('merges theme with existing settings', function () {
    $existingSettings = [
        'feature_flags' => ['dark_mode' => true],
        'notifications' => ['email' => true],
    ];

    $this->tenant->update(['settings' => $existingSettings]);

    $themeData = [
        'color' => 'purple',
        'font' => 'noto-sans',
    ];

    $response = $this->actingAs($this->user)
        ->patchJson("/landlord/tenants/{$this->tenant->id}/theme", $themeData);

    $response->assertSuccessful();

    $this->tenant->refresh();

    expect($this->tenant->settings['theme'])->toBe($themeData);
    expect($this->tenant->settings['feature_flags'])->toBe(['dark_mode' => true]);
    expect($this->tenant->settings['notifications'])->toBe(['email' => true]);
});

it('accepts partial theme updates', function () {
    // Set initial theme
    $this->tenant->update([
        'settings' => [
            'theme' => [
                'color' => 'blue',
                'font' => 'inter',
                'rounded' => 'medium',
                'variant' => 'default',
            ],
        ],
    ]);

    // Update only color
    $response = $this->actingAs($this->user)
        ->patchJson("/landlord/tenants/{$this->tenant->id}/theme", [
            'color' => 'green',
        ]);

    $response->assertSuccessful();

    $this->tenant->refresh();

    expect($this->tenant->settings['theme']['color'])->toBe('green');
});

it('returns updated tenant resource', function () {
    $themeData = [
        'color' => 'amber',
        'font' => 'figtree',
    ];

    $response = $this->actingAs($this->user)
        ->patchJson("/landlord/tenants/{$this->tenant->id}/theme", $themeData);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'slug',
            'domain',
            'settings',
            'status',
            'created_at',
            'updated_at',
        ],
    ]);

    expect($response->json('data.settings.theme'))->toBe($themeData);
});
