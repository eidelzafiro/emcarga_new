<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envío de push vía Expo Push API (https://exp.host/--/api/v2/push/send).
 *
 * Best-effort: si la red falla (bloqueo desde Cuba), se registra y se devuelve
 * false; la notificación in-app (`database`) sigue llegando al cliente.
 */
class ExpoPushSender implements PushSender
{
    public function __construct(
        private readonly string $endpoint,
        private readonly ?string $accessToken = null,
    ) {}

    public function send(string $token, string $titulo, string $cuerpo, array $data = []): bool
    {
        try {
            $request = Http::timeout(10)->acceptJson();

            if ($this->accessToken) {
                $request = $request->withToken($this->accessToken);
            }

            $response = $request->post($this->endpoint, [
                'to' => $token,
                'title' => $titulo,
                'body' => $cuerpo,
                'data' => $data,
                'sound' => 'default',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('push.expo_error', [
                'token' => substr($token, 0, 12).'…',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
