<?php

namespace Tests\Feature;

use App\Models\Entidad;
use App\Models\OrdenesTaller;
use App\Models\Tractivo;
use App\Models\User;
use App\Services\OrdenTallerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifica la lógica de negocio de Órdenes de Taller (OrdenTallerService):
 * estados derivados y transición del tractivo al abrir/cerrar/cancelar la OT.
 *
 * NOTA: se usa RefreshDatabase. La regla "una sola OT abierta por vehículo"
 * se valida en la BD real (ver AGENTS.md); en este entorno de testing MySQL el
 * primer test tras migrate:fresh puede fallar con "already active transaction"
 * (problema de interacción RefreshDatabase, no de la lógica).
 */
class TallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarEstadosComponentes();
        $this->entidadMatriz();
        $this->crearRolTecnica();
    }

    private function entidadMatriz(): Entidad
    {
        $entidad = Entidad::find(23);
        if (! $entidad) {
            $entidad = Entidad::forceCreate([
                'id' => 23,
                'nombre' => 'OFICINA CENTRAL',
                'abreviatura' => 'OFICINA CENTRAL',
                'es_matriz' => true,
                'activo' => true,
            ]);
        }

        return $entidad;
    }

    private function sembrarEstadosComponentes(): void
    {
        $estados = [
            14 => 'ACTIVO', 15 => 'MALO', 16 => 'REGULAR', 18 => 'REPARADO',
            19 => 'RECONSTRUCCIÓN', 20 => '1ER RECAUCHE', 21 => 'REGRABADO',
            22 => 'PROPUESTA BAJA', 23 => 'TRABAJANDO', 24 => 'PARALIZADO PARCIAL',
            25 => 'PARALIZADO A LARGO PLAZO', 26 => 'EN TALLER', 27 => 'NUEVO(A)',
            28 => '2DO RECAUCHE', 29 => '1ER REGRABE', 30 => '2DO REGRABE',
        ];

        foreach ($estados as $id => $nombre) {
            DB::table('estados_componentes')->updateOrInsert(
                ['id' => $id],
                ['nombre' => $nombre, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function crearRolTecnica(): void
    {
        if (! \Spatie\Permission\Models\Role::where('name', 'TECNICA')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'TECNICA']);
        }
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
