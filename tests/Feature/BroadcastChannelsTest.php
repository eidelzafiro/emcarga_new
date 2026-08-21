<?php

namespace Tests\Feature;

use App\Events\KpisUpdated;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * S-3 del PLAN.md: los canales Reverb deben autorizarse por perfil activo.
 *
 * Se valida directamente el callback del canal `perfil.{perfil}` (la lógica
 * de autorización por perfil) y que KpisUpdated se emite por perfil.
 *
 * Usa el baseline sembrado (DatabaseTransactions del TestCase), no RefreshDatabase,
 * para no reventar la BD de test compartida.
 */
class BroadcastChannelsTest extends TestCase
{
    private function callbackPerfil(): callable
    {
        return Broadcast::getChannels()->get('perfil.{perfil}');
    }

    public function test_canal_perfil_autoriza_rol_correspondiente(): void
    {
        $user = User::factory()->create();
        $user->assignRole('TECNICA');

        $this->assertTrue(($this->callbackPerfil())($user, 'TECNICA'));
    }

    public function test_canal_perfil_rechaza_rol_ajeno(): void
    {
        $user = User::factory()->create();
        $user->assignRole('COMERCIAL');

        $this->assertFalse(($this->callbackPerfil())($user, 'TECNICA'));
    }

    public function test_superadmin_accede_a_cualquier_perfil(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPERADMIN');

        $this->assertTrue(($this->callbackPerfil())($user, 'RECHUM'));
    }

    public function test_superadmin_emulando_perfil_accede_al_canal(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPERADMIN');

        Session::put('perfil_activo', 'CONTABILIDAD');

        $this->assertTrue(($this->callbackPerfil())($user, 'CONTABILIDAD'));
    }

    public function test_kpis_updated_emite_canal_privado_por_perfil(): void
    {
        $evento = new KpisUpdated(['label' => 'KPIs'], 'TECNICA');

        $canales = $evento->broadcastOn();

        $this->assertCount(1, $canales);
        $this->assertInstanceOf(PrivateChannel::class, $canales[0]);
        $this->assertSame('private-perfil.TECNICA', $canales[0]->name);
    }
}
