<?php

namespace Tests\Feature\Api;

use App\Jobs\EnviarPush;
use App\Models\ApiSyncLog;
use App\Models\DeviceToken;
use App\Models\Entidad;
use App\Models\User;
use App\Notifications\NotificacionSistema;
use App\Services\Push\PushSender;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Fase 8: dispositivos push, notificaciones in-app, job en cola y sync.
 */
class Fase8PushSyncTest extends TestCase
{
    private function usuario(): array
    {
        $entidad = Entidad::create([
            'nombre' => 'ENTIDAD F8',
            'abreviatura' => 'EF8',
            'activo' => true,
            'es_matriz' => false,
        ]);

        $user = User::factory()->create(['id_entidad' => $entidad->id]);
        $token = $user->createToken('test', ["entidad:{$entidad->id}"])->plainTextToken;

        return [$user, $token];
    }

    public function test_registra_token_de_dispositivo(): void
    {
        [$user, $token] = $this->usuario();

        $this->withToken($token)
            ->postJson('/api/v1/dispositivos', ['token' => 'ExponentPushToken[abc]', 'platform' => 'android'])
            ->assertOk();

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[abc]',
        ]);
    }

    public function test_reregistrar_token_no_duplica_y_actualiza_dueno(): void
    {
        [$user, $token] = $this->usuario();
        DeviceToken::create(['token' => 'ExponentPushToken[abc]', 'platform' => 'android', 'user_id' => $user->id]);

        $this->withToken($token)
            ->postJson('/api/v1/dispositivos', ['token' => 'ExponentPushToken[abc]'])
            ->assertOk();

        $this->assertSame(1, DeviceToken::where('token', 'ExponentPushToken[abc]')->count());
        $this->assertDatabaseHas('device_tokens', ['token' => 'ExponentPushToken[abc]', 'user_id' => $user->id]);
    }

    public function test_elimina_token_de_dispositivo(): void
    {
        [$user, $token] = $this->usuario();
        $user->deviceTokens()->create(['token' => 'tok-borrar', 'platform' => 'ios']);

        $this->withToken($token)
            ->deleteJson('/api/v1/dispositivos', ['token' => 'tok-borrar'])
            ->assertOk();

        $this->assertDatabaseMissing('device_tokens', ['token' => 'tok-borrar']);
    }

    public function test_job_enviar_push_recorre_los_dispositivos(): void
    {
        [$user] = $this->usuario();
        $user->deviceTokens()->create(['token' => 'tok-1']);
        $user->deviceTokens()->create(['token' => 'tok-2']);

        $fake = new class implements PushSender
        {
            public array $enviados = [];

            public function send(string $token, string $titulo, string $cuerpo, array $data = []): bool
            {
                $this->enviados[] = $token;

                return true;
            }
        };

        (new EnviarPush($user->id, 'Título', 'Cuerpo'))->handle($fake);

        sort($fake->enviados);
        $this->assertSame(['tok-1', 'tok-2'], $fake->enviados);
    }

    public function test_notificacion_sistema_encola_el_push(): void
    {
        Queue::fake();

        [$user] = $this->usuario();
        $user->notify(new NotificacionSistema('Hola', 'Mundo'));

        Queue::assertPushed(EnviarPush::class, fn ($job) => $job->userId === $user->id);
    }

    public function test_notificaciones_in_app_se_listan_y_marcan_leidas(): void
    {
        [$user, $token] = $this->usuario();
        $user->notify(new NotificacionSistema('Hola', 'Mundo'));

        $response = $this->withToken($token)->getJson('/api/v1/notificaciones');
        $response->assertOk()->assertJsonPath('pendientes', 1);
        $this->assertCount(1, $response->json('items'));

        $id = $response->json('items.0.id');

        $this->withToken($token)->postJson("/api/v1/notificaciones/{$id}/leer")->assertOk();
        $this->withToken($token)->getJson('/api/v1/notificaciones')->assertJsonPath('pendientes', 0);
    }

    public function test_sync_pull_devuelve_catalogos_y_registra_bitacora(): void
    {
        [, $token] = $this->usuario();

        $response = $this->withToken($token)->postJson('/api/v1/sync/pull');
        $response->assertOk()
            ->assertJsonStructure(['server_time', 'entidad_activa', 'fecha_operaciones', 'items', 'catalogos' => ['lugares', 'tipos_equipo']]);

        $this->assertDatabaseHas('api_sync_log', ['direction' => 'pull', 'endpoint' => 'sync/pull']);
        $this->assertGreaterThan(0, ApiSyncLog::count());
    }
}
