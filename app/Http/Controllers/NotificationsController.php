<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = $request->user()
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
                'creada' => $n->created_at->diffForHumans(),
            ]);

        $pendientes = $request->user()->unreadNotifications()->count();

        return response()->json([
            'items' => $notificaciones,
            'pendientes' => $pendientes,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = DatabaseNotification::findOrFail($id);

        if ($notification->notifiable_id !== $request->user()->id) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
