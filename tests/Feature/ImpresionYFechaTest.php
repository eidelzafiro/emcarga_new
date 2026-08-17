<?php

namespace Tests\Feature;

use App\Models\CartaPorte;
use App\Models\Moneda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica las correcciones de 2026-08-16/17:
 * 1) Las rutas de impresión exigen el permiso del módulo del recurso
 *    (carta-porte.ver, hojas-ruta.ver, facturas.ver) y no reportes.ver,
 *    de modo que COMERCIAL pueda imprimir.
 * 2) La fecha de emisión/parte de una CP debe quedar dentro del mes de
 *    operaciones (backend).
 */
class ImpresionYFechaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function usuarioComercial(): User
    {
        $user = User::factory()->create();
        $user->assignRole('COMERCIAL');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    private function datosCartaBase(): array
    {
        $this->session(['fecha_operaciones' => '2026-08-18']);
        Moneda::create(['id' => 1, 'codigo' => 'MN', 'nombre' => 'MN']);

        return [
            'numero' => 'CP-FECHA-'.rand(1000, 9999),
            'id_moneda' => 1,
            'fecha_emision' => '2026-08-10',
            'fecha_parte' => '2026-08-10',
            'toneladas' => 10,
            'estado' => 'emitida',
            'cancelada' => false,
        ];
    }

    public function test_impresion_carta_porte_disponible_para_comercial(): void
    {
        $carta = CartaPorte::create($this->datosCartaBase());

        $this->actingAs($this->usuarioComercial())
            ->get(route('carta-porte.imprimir', $carta))
            ->assertOk();
    }

    public function test_store_cp_rechaza_fecha_fuera_del_mes_de_operaciones(): void
    {
        $datos = $this->datosCartaBase();
        $datos['fecha_emision'] = '2026-07-31';

        $response = $this->actingAs($this->usuarioComercial())
            ->post(route('carta-porte.store'), $datos);

        $response->assertSessionHasErrors('fecha_emision');
    }

    public function test_store_cp_acepta_fecha_dentro_del_mes(): void
    {
        $datos = $this->datosCartaBase();

        $this->actingAs($this->usuarioComercial())
            ->post(route('carta-porte.store'), $datos)
            ->assertSessionHasNoErrors();
    }
}