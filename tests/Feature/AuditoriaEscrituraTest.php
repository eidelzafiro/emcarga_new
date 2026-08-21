<?php

namespace Tests\Feature;

use App\Http\Middleware\AuditoriaEscritura;
use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * S-2 del PLAN.md: el middleware AuditoriaEscritura debe registrar en
 * `bitacora` toda petición de escritura con ruta nombrada, y excluir
 * las de lectura y las de autenticación.
 */
class AuditoriaEscrituraTest extends TestCase
{
    public function test_audita_peticion_de_escritura(): void
    {
        Route::middleware(AuditoriaEscritura::class)
            ->post('/_audit_probe', fn () => response()->json(['ok' => true]))
            ->name('audit.probe');

        $user = User::factory()->create();
        $user->password_temporal = false;
        $user->save();

        $this->actingAs($user)
            ->postJson('/_audit_probe', ['foo' => 'bar'])
            ->assertOk();

        $this->assertDatabaseHas('bitacora', [
            'user_id' => $user->id,
            'accion' => 'post.audit.probe',
        ]);

        $entrada = Bitacora::where('accion', 'post.audit.probe')->first();
        $this->assertStringContainsString('IP:', $entrada->detalles);
        $this->assertStringContainsString('foo', $entrada->detalles);
        $this->assertStringNotContainsString('password', $entrada->detalles);
    }

    public function test_no_audita_peticion_de_lectura(): void
    {
        Route::middleware(AuditoriaEscritura::class)
            ->get('/_audit_get', fn () => response()->json(['ok' => true]))
            ->name('audit.get');

        $user = User::factory()->create();
        $user->password_temporal = false;
        $user->save();

        $this->actingAs($user)->getJson('/_audit_get')->assertOk();

        $this->assertDatabaseMissing('bitacora', ['accion' => 'get.audit.get']);
    }

    public function test_no_audita_ruta_sin_nombre(): void
    {
        Route::middleware(AuditoriaEscritura::class)
            ->post('/_audit_noname', fn () => response()->json(['ok' => true]));

        $user = User::factory()->create();
        $user->password_temporal = false;
        $user->save();

        $this->actingAs($user)->postJson('/_audit_noname')->assertOk();

        $this->assertDatabaseCount('bitacora', 0);
    }
}
