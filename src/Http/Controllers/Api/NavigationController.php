<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaravelRaptor\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Callcocam\LaravelRaptor\Services\NavigationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    public function __construct(
        protected NavigationService $navigationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $context = $request->get('context', 'tenant');
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => 'Não autenticado',
            ], 401);
        }

        $navigation = $this->navigationService->buildNavigation($user, $context);

        return response()->json([
            'navigation' => [
                $context => $navigation,
            ],
            'meta' => [
                'context' => $context,
                'timestamp' => now()->toIso8601String(),
                'user_id' => $user->id,
            ],
        ]);
    }

    public function clearCache(Request $request): JsonResponse
    {
        $this->navigationService->clearCache($request->user());

        return response()->json(['message' => 'Cache limpo com sucesso']);
    }
}
