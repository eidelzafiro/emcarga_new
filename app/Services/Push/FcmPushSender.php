<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envío de push vía Firebase Cloud Messaging (HTTP v1).
 *
 * El cliente móvil (Capacitor `@capacitor/push-notifications`) genera tokens
 * FCM, no tokens Expo, por lo que este sender es el proveedor real para
 * Android. Usa HTTP v1 (recomendado por Google, la API legacy está deprecada):
 * primero obtiene un access token OAuth 2.0 intercambiando un JWT firmado con
 * la private key del service account de Firebase y luego hace el envío a
 * `messages:send`.
 *
 * Best-effort: si la red falla (bloqueo desde Cuba) o faltan credenciales, se
 * registra y se devuelve false; la notificación in-app (`database`) sigue
 * llegando al cliente.
 */
class FcmPushSender implements PushSender
{
    public function __construct(
        private readonly string $projectId,
        private readonly string $clientEmail,
        private readonly string $privateKey,
        private readonly string $tokenUri = 'https://oauth2.googleapis.com/token',
        private readonly ?string $sendUri = null,
    ) {}

    public function send(string $token, string $titulo, string $cuerpo, array $data = []): bool
    {
        try {
            $accessToken = $this->accessToken();

            if ($accessToken === null) {
                Log::warning('push.fcm_sin_token_oauth', ['project' => $this->projectId]);

                return false;
            }

            $url = (! empty($this->sendUri))
                ? $this->sendUri
                : "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $response = Http::timeout(10)
                ->acceptJson()
                ->withToken($accessToken)
                ->post($url, [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $titulo,
                            'body' => $cuerpo,
                        ],
                        'data' => array_map('strval', $data),
                        'android' => ['priority' => 'high'],
                        'apns' => [
                            'payload' => [
                                'aps' => ['sound' => 'default'],
                            ],
                        ],
                    ],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('push.fcm_error', [
                'project' => $this->projectId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function accessToken(): ?string
    {
        $clave = 'fcm.access.'.$this->projectId;

        $token = Cache::get($clave);
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $token = $this->requestAccessToken();
        if ($token !== null) {
            Cache::put($clave, $token, 3300); // ~55 min (los tokens OAuth viven 1 h)
        }

        return $token;
    }

    /**
     * Obtiene el access token intercambiando un JWT firmado RS256 por la
     * private key del service account de Firebase.
     */
    private function requestAccessToken(): ?string
    {
        $jwt = $this->signedJwt();
        if ($jwt === null) {
            return null;
        }

        $response = Http::timeout(10)
            ->asForm()
            ->post($this->tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        return $response->successful() ? $response->json('access_token') : null;
    }

    private function signedJwt(): ?string
    {
        $header = $this->base64urlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now = time();

        $claims = $this->base64urlEncode(json_encode([
            'iss' => $this->clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $this->tokenUri,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = $header.'.'.$claims;

        $key = $this->normalizePrivateKey();
        if ($key === null || ! openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            Log::warning('push.fcm_llave_invalida');

            return null;
        }

        return $unsigned.'.'.$this->base64urlEncode($signature);
    }

    /**
     * Acepta la llave tal cual llega de Firebase (con saltos de línea reales)
     * o escapada en el .env (literal \n); en este último caso se desescapa.
     */
    private function normalizePrivateKey(): ?string
    {
        $key = str_replace('\\n', "\n", trim((string) $this->privateKey));

        if (str_contains($key, 'BEGIN') === false && $key !== '') {
            $key = "-----BEGIN PRIVATE KEY-----\n{$key}\n-----END PRIVATE KEY-----\n";
        }

        return $key === '' ? null : $key;
    }

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}