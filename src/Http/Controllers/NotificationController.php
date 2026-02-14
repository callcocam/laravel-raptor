<?php

namespace Callcocam\LaravelRaptor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Lista todas as notificações do usuário autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $notifications = $request->user()->notifications()
                ->latest()
                ->take(50)
                ->when(config('app.current_tenant_id'), function ($query, $tenantId) {
                    if ($tenantId) {
                        $query->where('tenant_id', $tenantId);
                    }
                })
                ->when(config('app.current_client_id'), function ($query, $clientId) {
                    if ($clientId) {
                        $query->where('client_id', $clientId);
                    }
                })
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->data['type'] ?? 'info',
                        'title' => $notification->data['title'] ?? 'Notificação',
                        'message' => $notification->data['message'] ?? null,
                        'data' => $notification->data,
                        'read_at' => $notification->read_at,
                        'created_at' => $notification->created_at->toISOString(),
                    ];
                });

            return response()->json([
                'notifications' => $notifications,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao carregar notificações', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'notifications' => [],
                'error' => 'Não foi possível carregar as notificações.',
            ], 500);
        }
    }

    /**
     * Marca uma notificação como lida.
     */
    public function markAsRead(Request $request, string $id)
    {
        try {
            $notification = $request->user()->notifications()->find($id);

            if (! $notification) {
                return back()->with('error', 'Notificação não encontrada.');
            }

            $notification->markAsRead();

            return back()->with('success', 'Notificação marcada como lida.');
        } catch (\Exception $e) {
            Log::error('Erro ao marcar notificação como lida', [
                'user_id' => $request->user()?->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Não foi possível marcar a notificação como lida.');
        }
    }

    /**
     * Marca todas as notificações como lidas.
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $request->user()->unreadNotifications->markAsRead();

            return back()->with('success', 'Todas as notificações foram marcadas como lidas.');
        } catch (\Exception $e) {
            Log::error('Erro ao marcar todas notificações como lidas', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Não foi possível marcar as notificações como lidas.');
        }
    }

    /**
     * Remove uma notificação.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            $notification = $request->user()->notifications()->find($id);

            if (! $notification) {
                return back()->with('error', 'Notificação não encontrada.');
            }

            $notification->delete();

            return back()->with('success', 'Notificação removida.');
        } catch (\Exception $e) {
            Log::error('Erro ao remover notificação', [
                'user_id' => $request->user()?->id,
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Não foi possível remover a notificação.');
        }
    }

    /**
     * Remove todas as notificações do usuário.
     */
    public function destroyAll(Request $request)
    {
        try {
            $request->user()->notifications()->delete();

            return back()->with('success', 'Todas as notificações foram removidas.');
        } catch (\Exception $e) {
            Log::error('Erro ao remover todas notificações', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Não foi possível remover as notificações.');
        }
    }
}
