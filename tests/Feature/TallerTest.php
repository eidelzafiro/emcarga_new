<?php

namespace Tests\Feature;

use App\Models\OrdenesTaller;
use App\Models\Tractivo;
use App\Models\User;
use App\Services\OrdenTallerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function usuarioTecnica(): User
    {
        $user = User::factory()->create();
        $user->assignRole('TECNICA');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_taller_index()
    {
        $response = $this->actingAs($this->usuarioTecnica())->get(route('taller.index'));
        $response->assertOk();
    }

    public function test_una_sola_ot_abierta_por_vehiculo()
    {
        $tractivo = Tractivo::factory()->create(['id_tipo_vehiculo' => null]);
        $svc = app(OrdenTallerService::class);

        $primera = $svc->crear(['id_tractivo' => $tractivo->id, 'fecha_ingreso' => '2026-08-17'], 23);

        $this->assertNull($primera->fecha_salida);
        $this->assertEquals('abierta', $primera->estado);

        $this->expectException(\InvalidArgumentException::class);
        $svc->crear(['id_tractivo' => $tractivo->id, 'fecha_ingreso' => '2026-08-18'], 23);
    }

    public function test_cerrar_ot_pasa_tractivo_a_activo()
    {
        $tractivo = Tractivo::factory()->create(['id_tipo_vehiculo' => null, 'id_tipo_estado' => 26]);
        $svc = app(OrdenTallerService::class);

        $ot = $svc->crear(['id_tractivo' => $tractivo->id, 'fecha_ingreso' => '2026-08-17'], 23);
        $this->assertEquals(26, $ot->tractivo->id_tipo_estado); // EN TALLER

        $svc->cerrar($ot, '2026-08-18', '15:00');

        $this->assertEquals('cerrada', $ot->fresh()->estado);
        $this->assertNotNull($ot->fresh()->fecha_salida);
        $this->assertEquals(14, $ot->tractivo->fresh()->id_tipo_estado); // ACTIVO
    }

    public function test_cancelar_ot()
    {
        $tractivo = Tractivo::factory()->create(['id_tipo_vehiculo' => null]);
        $svc = app(OrdenTallerService::class);

        $ot = $svc->crear(['id_tractivo' => $tractivo->id, 'fecha_ingreso' => '2026-08-17'], 23);
        $svc->cancelar($ot);

        $this->assertTrue($ot->fresh()->cancelada);
        $this->assertEquals('cancelada', $ot->fresh()->estado);
    }
}
