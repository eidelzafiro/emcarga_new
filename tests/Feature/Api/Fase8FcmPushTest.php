<?php

namespace Tests\Feature\Api;

use App\Services\Push\FcmPushSender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fase 8 (push real): FCM HTTP v1 — obtención de access token vía JWT y envío.
 */
class Fase8FcmPushTest extends TestCase
{
    private const PROJECT = 'emcarga-test';
    private const EMAIL = 'firebase-adminsdk-abc@emcarga-test.iam.gserviceaccount.com';

    private function llavePrivada(): string
    {
        $pkey = openssl_pkey_new(['private_key_bits' => 1024, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        openssl_pkey_export($pkey, $pem);

        return $pem;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_firma_jwt_y_obtiene_access_token_antes_de_enviar(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.token', 'expires_in' => 3600], 200),
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/emcarga-test/messages/xyz'], 200),
        ]);

        $sender = new FcmPushSender(self::PROJECT, self::EMAIL, $this->llavePrivada());
        $ok = $sender->send('fcm-token-1', 'Título', 'Cuerpo', ['screen' => '/aforos']);

        $this->assertTrue($ok);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://fcm.googleapis.com/v1/projects/emcarga-test/messages:send') {
                return false;
            }

            $this->assertStringStartsWith('Bearer ya29.token', $request->header('Authorization')[0]);

            $body = $request->data();
            $this->assertSame('fcm-token-1', $body['message']['token']);
            $this->assertSame('Título', $body['message']['notification']['title']);
            $this->assertSame('Cuerpo', $body['message']['notification']['body']);
            $this->assertSame('/aforos', $body['message']['data']['screen']);
            $this->assertSame('high', $body['message']['android']['priority']);

            return true;
        });
    }

    public function test_access_token_se_cachea_entre_envios(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.cacheado', 'expires_in' => 3600]),
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'ok'], 200),
        ]);

        $sender = new FcmPushSender(self::PROJECT, self::EMAIL, $this->llavePrivada());
        $sender->send('t1', 'A', 'B');
        $sender->send('t2', 'A', 'B');

        Http::assertSentCount(3); // 1 token + 2 envíos
    }

    public function test_devuelve_false_si_oauth_falla(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400),
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'no-debe-suceder'], 200),
        ]);

        $sender = new FcmPushSender(self::PROJECT, self::EMAIL, $this->llavePrivada());
        $this->assertFalse($sender->send('t1', 'A', 'B'));
    }

    public function test_devuelve_false_si_el_envio_falla(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.x', 'expires_in' => 3600]),
            'https://fcm.googleapis.com/*' => Http::response(['error' => 'error'], 503),
        ]);

        $sender = new FcmPushSender(self::PROJECT, self::EMAIL, $this->llavePrivada());
        $this->assertFalse($sender->send('t1', 'A', 'B'));
    }

    public function test_acepta_llave_con_saltos_escapados(): void
    {
        $scaped = str_replace("\n", '\\n', $this->llavePrivada());
        $sender = new FcmPushSender(self::PROJECT, self::EMAIL, $scaped);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.esc', 'expires_in' => 3600]),
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'ok'], 200),
        ]);

        $this->assertTrue($sender->send('t1', 'A', 'B'));
    }
}