<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\CartaPorte;
use App\Models\Lugare;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Tarifa;
use App\Models\TipoCarga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Aforos (Fase 3): formulario de cálculo en vivo y guardado.
 * El permiso de aforos se resuelve vía ALIAS_MODULO → facturas (facturas.ver).
 */
class AforosFeatureTest extends TestCase
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

    private function catalogoMinimo(): void
    {
        TipoCarga::query()->forceCreate(['id' => 3, 'codigo' => '3', 'nombre' => 'Contenedor']);
        TipoCarga::query()->forceCreate(['id' => 2, 'codigo' => '2', 'nombre' => 'Carga General']);
        Tarifa::create(['id_tipo_carga' => 3, 'kms' => 100, 'tarifa_mt' => 50, 'version' => '46']);
        Moneda::create(['id' => 1, 'codigo' => 'MN', 'nombre' => 'MN']);
        Lugare::create(['nombre' => 'Origen', 'activo' => true]);
        Lugare::create(['nombre' => 'Destino', 'activo' => true]);
        Producto::create(['codigo' => 'P1', 'nombre' => 'Producto 1', 'activo' => true]);
    }

    /**
     * Crea una carta de porte ya girada, del mes de operaciones y no aforada,
     * para el formulario de aforo (paridad legacy: el aforo selecciona una CP).
     */
    private function cartaPendiente(array $attrs = []): CartaPorte
    {
        $this->session(['fecha_operaciones' => now()->toDateString()]);

        $tipo = TipoCarga::where('id', 3)->first() ?? TipoCarga::query()->forceCreate(['id' => 3, 'codigo' => '3', 'nombre' => 'Contenedor']);

        return CartaPorte::create(array_merge([
            'numero' => 'CP-TEST-'.rand(1000, 9999),
            'id_tipo_carga' => $tipo->id,
            'id_moneda' => 1,
            'distancia' => 100,
            'toneladas' => 10,
            'fecha_emision' => now()->toDateString(),
            'fecha_parte' => now()->toDateString(),
            'estado' => 'emitida',
            'cancelada' => false,
        ], $attrs));
    }

    public function test_aforos_index(): void
    {
        $this->actingAs($this->usuarioComercial())
            ->get(route('aforos.index'))
            ->assertOk();
    }

    public function test_aforos_create_muestra_formulario(): void
    {
        $this->catalogoMinimo();

        $this->actingAs($this->usuarioComercial())
            ->get(route('aforos.create'))
            ->assertOk();
    }

    public function test_cotizar_devuelve_tarifa(): void
    {
        $this->catalogoMinimo();

        $this->actingAs($this->usuarioComercial())
            ->postJson(route('aforos.cotizar'), [
                'moneda' => 1,
                'tipocarga' => 3,
                'distancia' => 100,
                'peso' => 10,
                'descuento' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('tarmt', 50)
            ->assertJsonPath('fletemt', 500);
    }

    public function test_crear_aforo(): void
    {
        $this->catalogoMinimo();
        $cliente = Cliente::create(['codigo' => 'CL-A', 'nombre' => 'Cliente Aforo']);
        $carta = $this->cartaPendiente(['id_cliente' => $cliente->id]);

        $this->actingAs($this->usuarioComercial())
            ->post(route('aforos.store'), [
                'id_carta_porte' => $carta->id,
                'fecha_parte' => now()->toDateString(),
                'flete_mt' => 500,
                'ingreso_mt' => 500,
            ])
            ->assertRedirect(route('aforos.index'));

        $this->assertDatabaseHas('aforos', ['id_carta_porte' => $carta->id, 'flete_mt' => 500, 'ingreso_mt' => 500]);
    }

    public function test_crear_aforo_valida_campos_requeridos(): void
    {
        $this->actingAs($this->usuarioComercial())
            ->post(route('aforos.store'), [])
            ->assertSessionHasErrors(['id_carta_porte', 'fecha_parte', 'flete_mt', 'ingreso_mt']);
    }
}
