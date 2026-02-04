<?php

namespace Callcocam\LaravelRaptor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    /**
     * Lista todas as notificações do usuário autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->latest()
            ->take(50)
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
    }

    /**
     * Marca uma notificação como lida.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->find($id);
        
        if ($notification) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notificação marcada como lida.');
    }

    /**
     * Marca todas as notificações como lidas.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Todas as notificações foram marcadas como lidas.');
    }

    /**
     * Remove uma notificação.
     */
    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->find($id);
        
        if ($notification) {
            $notification->delete();
        }

        return back()->with('success', 'Notificação removida.');
    }

    /**
     * Remove todas as notificações do usuário.
     */
    public function destroyAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return back()->with('success', 'Todas as notificações foram removidas.');
    }
}
