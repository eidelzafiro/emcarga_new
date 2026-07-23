<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Facturación (Fase 5.4): facturas, prefacturas y acciones
 * de ciclo de vida (cancelar, refacturar, firmar, cobrar).
 */
class FacturacionTest extends TestCase
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

    private function clienteValido(): Cliente
    {
        return Cliente::create(['codigo' => 'CL-T', 'nombre' => 'Cliente Test']);
    }

    private function datosFacturaValidos(Cliente $cliente, int $numero = 8001): array
    {
        return [
            'numero' => $numero,
            'fecha_emision' => now()->toDateString(),
            'id_cliente' => $cliente->id,
            'flete_mt' => 100,
            'flete_mlc' => 50,
            'flete_demora' => 0,
            'otros_mt' => 10,
            'ingreso_mt' => 160,
            'oventas' => false,
            'notas' => 'Factura de prueba',
        ];
    }

    public function test_facturas_index(): void
    {
        $this->actingAs($this->usuarioComercial())
            ->get(route('facturas.index'))
            ->assertOk();
    }

    public function test_facturas_create_muestra_formulario(): void
    {
        $this->actingAs($this->usuarioComercial())
            ->get(route('facturas.create'))
            ->assertOk();
    }

    public function test_crear_factura(): void
    {
        $cliente = $this->clienteValido();

        $this->actingAs($this->usuarioComercial())
            ->post(route('facturas.store'), $this->datosFacturaValidos($cliente))
            ->assertRedirect();

        $this->assertDatabaseHas('facturas', [
            'numero' => 8001,
            'id_cliente' => $cliente->id,
            'estado' => 'emitida',
        ]);
    }

    public function test_crear_factura_valida_campos_requeridos(): void
    {
        $this->actingAs($this->usuarioComercial())
            ->post(route('facturas.store'), [])
            ->assertSessionHasErrors(['numero', 'fecha_emision', 'id_cliente', 'flete_mt', 'flete_mlc', 'ingreso_mt']);
    }

    public function test_numero_de_factura_es_unico(): void
    {
        $cliente = $this->clienteValido();
        $datos = $this->datosFacturaValidos($cliente);
        Factura::create($datos + ['estado' => 'emitida']);

        $this->actingAs($this->usuarioComercial())
            ->post(route('facturas.store'), $datos)
            ->assertSessionHasErrors('numero');
    }

    public function test_facturas_show(): void
    {
        $factura = Factura::create($this->datosFacturaValidos($this->clienteValido()) + ['estado' => 'emitida']);

        $this->actingAs($this->usuarioComercial())
            ->get(route('facturas.show', $factura))
            ->assertOk();
    }

    public function test_cancelar_factura(): void
    {
        $factura = Factura::create($this->datosFacturaValidos($this->clienteValido()) + ['estado' => 'emitida']);

        $this->actingAs($this->usuarioComercial())
            ->post(route('facturas.cancelar', $factura), ['motivo' => 'Error de emisión'])
            ->assertRedirect();

        $this->assertTrue($factura->fresh()->cancelada);
    }

    public function test_prefacturas_index(): void
    {
        $this->actingAs($this->usuarioComercial())
            ->get(route('prefacturas.index'))
            ->assertOk();
    }

    public function test_tipo_ingresos_index(): void
    {
        $this->actingAs($this->usuarioComercial())
            ->get(route('tipo-ingresos.index'))
            ->assertOk();
    }

    public function test_modulo_cerrado_para_otros_perfiles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('TECNICA');
        $user->password_temporal = false;
        $user->save();

        $this->actingAs($user)->get(route('facturas.index'))->assertForbidden();
        $this->actingAs($user)->get(route('prefacturas.index'))->assertForbidden();
    }
}
