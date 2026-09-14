<?php

namespace App\Services\Push;

/**
 * Contrato de envío de notificaciones push a un dispositivo móvil.
 *
 * La implementación real (Expo) es best-effort: en Cuba el acceso a FCM/Expo
 * puede estar bloqueado, por lo que siempre existe un fallback in-app
 * (notificación `database`) y un `NullPushSender` que solo registra en log.
 */
interface PushSender
{
    /**
     * @param  array<string,mixed>  $data  Datos extra que viajan con la notificación.
     * @return bool  true si el proveedor aceptó el envío.
     */
    public function send(string $token, string $titulo, string $cuerpo, array $data = []): bool;
}
