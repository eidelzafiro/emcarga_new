<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Notificaciones in-app del usuario autenticado (Fase 8). El cliente móvil las
 * consulta como fallback garantizado del push (Cuba: FCM/Expo bloqueado).
 */
class NotificacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()
            ->notifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'titulo' => $n->data['titulo'] ?? 'Sin título',
                'cuerpo' => $n->data['cuerpo'] ?? '',
                'tipo' => $n->data['tipo'] ?? 'info',
                'url' => $n->data['url'] ?? null,
                'icono' => $n->data['icono'] ?? 'pi pi-info-circle',
                'leida' => $n->read_at !== null,
                'creada' => $n->created_at->toIso8601String(),
            ]);

        return response()->json([
            'items' => $items,
            'pendientes' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function leer(Request $request, string $id): JsonResponse
    {
        $notificacion = DatabaseNotification::findOrFail($id);

        if ((int) $notificacion->notifiable_id !== (int) $request->user()->id) {
            abort(403);
        }

        $notificacion->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function leerTodas(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}
