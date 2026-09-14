<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Log;

/**
 * Implementación por defecto (offline-first): no envía push real, solo deja
 * traza. El usuario recibe la notificación in-app (canal `database`).
 */
class NullPushSender implements PushSender
{
    public function send(string $token, string $titulo, string $cuerpo, array $data = []): bool
    {
        Log::info('push.omitido', [
            'token' => substr($token, 0, 12).'…',
            'titulo' => $titulo,
        ]);

        return false;
    }
}
