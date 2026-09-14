<?php

namespace App\Listeners;

use App\Jobs\EnviarPush;
use App\Models\User;
use App\Notifications\NotificacionSistema;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Cuando se persiste una NotificacionSistema (canal `database`), encola el
 * envío push a los dispositivos del usuario. Desacopla el push (best-effort,
 * puede fallar por bloqueo de red) de la notificación in-app ya garantizada.
 */
class EnviarPushNotificacion
{
    public function handle(NotificationSent $event): void
    {
        if (! $event->notification instanceof NotificacionSistema) {
            return;
        }

        if ($event->channel !== 'database' || ! $event->notifiable instanceof User) {
            return;
        }

        EnviarPush::dispatch(
            $event->notifiable->id,
            $event->notification->titulo,
            $event->notification->cuerpo,
            [
                'tipo' => $event->notification->tipo,
                'url' => $event->notification->url,
                'icono' => $event->notification->icono,
            ],
        );
    }
}
