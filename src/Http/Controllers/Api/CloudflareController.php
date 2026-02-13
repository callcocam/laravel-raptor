<?php

/**
 * Created by Claudio Campos.
 * User: callcocam, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Callcocam\LaravelRaptor\Services\Cloudflare\CloudflareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API para o campo CloudflareDnsField (listar zones, registros, criar e apagar DNS).
 */
class CloudflareController extends Controller
{
    public function __construct(
        protected CloudflareService $cloudflare
    ) {}

    /**
     * Lista zones (domínios) da conta Cloudflare.
     */
    public function zones(Request $request): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('Cloudflare API token not configured.'),
            ], 503);
        }

        $result = $this->cloudflare->listZones(
            $request->input('name'),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 50)
        );

        if (! ($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        $zones = $result['result'] ?? [];
        $data = is_array($zones) ? $zones : (isset($zones['result']) ? $zones['result'] : []);

        return response()->json([
            'success' => true,
            'zones' => $data,
        ]);
    }

    /**
     * Lista registros DNS de uma zone.
     */
    public function records(Request $request, string $zoneId): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('Cloudflare API token not configured.'),
            ], 503);
        }

        $result = $this->cloudflare->listRecords(
            $zoneId,
            $request->input('type'),
            $request->input('name'),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 100)
        );

        if (! ($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        $records = $result['result'] ?? [];
        $data = is_array($records) ? $records : (isset($records['result']) ? $records['result'] : []);

        return response()->json([
            'success' => true,
            'records' => $data,
        ]);
    }

    /**
     * Cria um registro DNS na zone.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => 'required|string',
            'type' => 'required|string|in:A,AAAA,CNAME,TXT,MX,NS,SRV,CAA',
            'name' => 'required|string',
            'content' => 'required|string',
            'ttl' => 'nullable|integer|min:1|max:86400',
            'proxied' => 'nullable|boolean',
            'comment' => 'nullable|string|max:255',
        ]);

        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('Cloudflare API token not configured.'),
            ], 503);
        }

        $result = $this->cloudflare->createRecord($validated['zone_id'], [
            'type' => $validated['type'],
            'name' => $validated['name'],
            'content' => $validated['content'],
            'ttl' => $validated['ttl'] ?? 1,
            'proxied' => $validated['proxied'] ?? false,
            'comment' => $validated['comment'] ?? null,
        ]);

        if (! ($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success' => true,
            'record' => $result['result'] ?? null,
        ]);
    }

    /**
     * Remove um registro DNS.
     */
    public function destroy(string $zoneId, string $recordId): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('Cloudflare API token not configured.'),
            ], 503);
        }

        $result = $this->cloudflare->deleteRecord($zoneId, $recordId);

        if (! ($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        return response()->json(['success' => true]);
    }
}
