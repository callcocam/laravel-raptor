<?php

use Callcocam\LaravelRaptor\Services\Cloudflare\CloudflareService;
use Illuminate\Support\Facades\Http;

uses(\Tests\TestCase::class);

beforeEach(function () {
    config(['raptor.cloudflare.api_token' => '']);
});

it('returns not configured when api token is empty', function () {
    $service = new CloudflareService;

    expect($service->isConfigured())->toBeFalse();
});

it('returns configured when api token is set', function () {
    $service = new CloudflareService('fake-token');

    expect($service->isConfigured())->toBeTrue();
});

it('listZones returns success false when not configured', function () {
    $service = new CloudflareService;

    $result = $service->listZones();

    expect($result)->toHaveKeys(['success', 'errors']);
    expect($result['success'])->toBeFalse();
    expect($result['errors'])->toBeArray();
});

it('listZones returns zones when configured and API responds successfully', function () {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => true,
            'result' => [
                ['id' => 'zone-1', 'name' => 'example.com', 'status' => 'active'],
            ],
        ], 200),
    ]);

    $service = new CloudflareService('fake-token');

    $result = $service->listZones();

    expect($result['success'])->toBeTrue();
    expect($result['result'])->toBeArray();
    expect($result['result'][0]['name'])->toBe('example.com');
});

it('createRecord returns success when API accepts', function () {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => true,
            'result' => [
                'id' => 'rec-1',
                'type' => 'A',
                'name' => 'www.example.com',
                'content' => '1.2.3.4',
            ],
        ], 200),
    ]);

    $service = new CloudflareService('fake-token');

    $result = $service->createRecord('zone-1', [
        'type' => 'A',
        'name' => 'www.example.com',
        'content' => '1.2.3.4',
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['result']['id'])->toBe('rec-1');
});

it('deleteRecord returns success when API accepts', function () {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => true,
            'result' => ['id' => 'rec-1'],
        ], 200),
    ]);

    $service = new CloudflareService('fake-token');

    $result = $service->deleteRecord('zone-1', 'rec-1');

    expect($result['success'])->toBeTrue();
});
