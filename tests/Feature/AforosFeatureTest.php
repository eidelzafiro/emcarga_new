<?php

namespace Tests\Feature;

use App\Models\Aforo;
use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Lugare;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Tarifa;
use App\Models\TipoCarga;
use App\Models\User;
use Tests\TestCase;

/**
 * Módulo Aforos (Fase 3): formulario de cálculo en vivo y guardado.
 * El permiso de aforos se resuelve vía ALIAS_MODULO → facturas (facturas.ver).
 */
class AforosFeatureTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
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

        // Fase 4d: el equipo/cliente/tipos/productos se derivan de la HR y la
        // solicitud; la carta solo conserva lugares/fechas/pesos/distancia.
        return CartaPorte::create(array_merge([
            'numero' => 'CP-TEST-'.rand(1000, 9999),
            'id_moneda' => 1,
            'id_lugar_origen' => Lugare::first()?->id,
            'id_lugar_destino' => Lugare::skip(1)->first()?->id,
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
        $carta = $this->cartaPendiente();

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
            ->assertSessionHasErrors(['fecha_parte', 'flete_mt', 'ingreso_mt']);
    }

    public function test_editar_aforo_muestra_formulario(): void
    {
        $this->catalogoMinimo();
        $carta = $this->cartaPendiente();
        $aforo = Aforo::create([
            'id_carta_porte' => $carta->id,
            'fecha_parte' => now()->toDateString(),
            'flete_mt' => 500,
            'ingreso_mt' => 500,
            'id_user' => auth()->id(),
        ]);

        $this->actingAs($this->usuarioComercial())
            ->get(route('aforos.edit', $aforo))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Aforos/Form')
                ->has('cartaPreseleccionada'));
    }

    public function test_actualizar_aforo(): void
    {
        $this->catalogoMinimo();
        $carta = $this->cartaPendiente();
        $aforo = Aforo::create([
            'id_carta_porte' => $carta->id,
            'fecha_parte' => now()->toDateString(),
            'flete_mt' => 500,
            'ingreso_mt' => 500,
            'id_user' => auth()->id(),
        ]);

        $this->actingAs($this->usuarioComercial())
            ->put(route('aforos.update', $aforo), [
                'id_carta_porte' => $carta->id,
                'fecha_parte' => now()->toDateString(),
                'flete_mt' => 750,
                'ingreso_mt' => 750,
            ])
            ->assertRedirect(route('aforos.index'));

        $this->assertDatabaseHas('aforos', [
            'id' => $aforo->id,
            'flete_mt' => 750,
            'ingreso_mt' => 750,
        ]);
    }

    public function test_actualizar_aforo_facturado_es_403(): void
    {
        $this->catalogoMinimo();
        $carta = $this->cartaPendiente();
        $cliente = \App\Models\Cliente::create(['nombre' => 'Cliente Factura Test']);
        $factura = \App\Models\Factura::create([
            'numero' => 999001,
            'id_cliente' => $cliente->id,
            'id_user' => auth()->id(),
            'fecha_emision' => now()->toDateString(),
        ]);
        $aforo = Aforo::create([
            'id_carta_porte' => $carta->id,
            'fecha_parte' => now()->toDateString(),
            'flete_mt' => 500,
            'ingreso_mt' => 500,
            'id_user' => auth()->id(),
            'id_factura' => $factura->id,
        ]);

        $this->actingAs($this->usuarioComercial())
            ->put(route('aforos.update', $aforo), [
                'fecha_parte' => now()->toDateString(),
                'flete_mt' => 750,
                'ingreso_mt' => 750,
            ])
            ->assertForbidden();
    }

    public function test_cotizar_demora_devuelve_json(): void
    {
        $this->catalogoMinimo();

        $this->actingAs($this->usuarioComercial())
            ->postJson(route('aforos.cotizar-demora'), [
                'tipocarga1' => 3, 'capacidad' => 10, 'horas' => 5,
            ])
            ->assertOk()
            ->assertJsonStructure(['tardem1', 'fletedemt']);
    }

    public function test_cotizar_almacenaje_devuelve_json(): void
    {
        $this->catalogoMinimo();

        $this->actingAs($this->usuarioComercial())
            ->postJson(route('aforos.cotizar-almacenaje'), [
                'alm_peso' => 10, 'alm_horas' => 48, 'tipocarga' => 3,
            ])
            ->assertOk()
            ->assertJsonStructure(['alm_tarifa', 'alm_flete']);
    }

    public function test_cotizar_salario_devuelve_json(): void
    {
        $this->catalogoMinimo();

        $this->actingAs($this->usuarioComercial())
            ->postJson(route('aforos.cotizar-salario'), [
                'tipocarga' => 3, 'capacidad' => 10, 'distancia' => 100,
                'ingresos' => 500, 'almacenaje' => 0,
            ])
            ->assertOk()
            ->assertJsonStructure(['salario', 'tasa']);
    }

    public function test_cotizar_tiempos_devuelve_json(): void
    {
        $this->catalogoMinimo();

        $this->actingAs($this->usuarioComercial())
            ->postJson(route('aforos.cotizar-tiempos'), [
                'movimiento' => 2, 'carga' => 1, 'descarga' => 1, 'otros' => 0,
            ])
            ->assertOk()
            ->assertJsonStructure(['ttotal']);
    }

    public function test_cotizar_indicadores_devuelve_json(): void
    {
        $this->catalogoMinimo();

        $this->actingAs($this->usuarioComercial())
            ->postJson(route('aforos.cotizar-indicadores'), [
                'tipo' => 1, 'viajes' => 1, 'filas' => [],
            ])
            ->assertOk()
            ->assertJsonStructure(['tipo', 'kmcarga_total']);
    }

    public function test_index_sin_permiso_es_403(): void
    {
        $user = User::factory()->create();
        $user->password_temporal = false;
        $user->save();

        $this->actingAs($user)
            ->get(route('aforos.index'))
            ->assertForbidden();
    }
}
