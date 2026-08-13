<?php

namespace Tests\Feature;

use App\Models\Aforo;
use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Prefactura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flujo prefactura → factura: crear prefactura con aforos pendientes y
 * convertirlos en una factura (id_prefactura → id_factura, estado procesada).
 */
class PrefacturasFacturarTest extends TestCase
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

    private function cliente(): Cliente
    {
        return Cliente::create(['codigo' => 'CL-PF-'.uniqid(), 'nombre' => 'Cliente Prefactura']);
    }

    private function aforoPendiente(int $ingreso = 500): Aforo
    {
        $cliente = $this->cliente();
        $cp = CartaPorte::create([
            'numero' => 'CP-'.uniqid(),
            'id_cliente' => $cliente->id,
            'toneladas' => 10,
            'fecha_emision' => now()->toDateString(),
            'fecha_parte' => now()->toDateString(),
            'estado' => 'abierta',
        ]);

        return Aforo::create([
            'id_carta_porte' => $cp->id,
            'fecha_parte' => now()->toDateString(),
            'flete_mt' => $ingreso,
            'ingreso_mt' => $ingreso,
            'refactura' => false,
        ]);
    }

    public function test_prefactura_index_ok(): void
    {
        $this->actingAs($this->usuarioComercial())
            ->get(route('prefacturas.index'))
            ->assertOk();
    }

    public function test_prefactura_create_muestra_formulario(): void
    {
        $this->actingAs($this->usuarioComercial())
            ->get(route('prefacturas.create'))
            ->assertOk()
            ->assertInertia(fn ($assert) => $assert->component('Prefacturas/Form'));
    }

    public function test_crear_prefactura_auto_numera_y_asigna_aforos(): void
    {
        $cliente = $this->cliente();
        $aforo = $this->aforoPendiente(500);

        $this->actingAs($this->usuarioComercial())
            ->post(route('prefacturas.store'), [
                'id_cliente' => $cliente->id,
                'fecha' => now()->toDateString(),
                'flete_mt' => 500,
                'flete_mlc' => 0,
                'flete_demora' => 0,
                'otros_mt' => 0,
                'ingreso_mt' => 500,
                'aforos_ids' => [$aforo->id],
            ])
            ->assertRedirect(route('prefacturas.index'));

        $prefactura = Prefactura::first();
        $this->assertNotNull($prefactura);
        $this->assertStringStartsWith('PF-', $prefactura->numero);
        $this->assertSame('pendiente', $prefactura->estado);
        $this->assertDatabaseHas('aforos', ['id' => $aforo->id, 'id_prefactura' => $prefactura->id]);
    }

    public function test_facturar_prefactura_crea_factura_y_mueve_aforos(): void
    {
        $cliente = $this->cliente();
        $aforo = $this->aforoPendiente(500);
        $prefactura = Prefactura::create([
            'numero' => 'PF-2026-0001',
            'id_cliente' => $cliente->id,
            'fecha' => now()->toDateString(),
            'flete_mt' => 500,
            'flete_mlc' => 0,
            'flete_demora' => 0,
            'otros_mt' => 0,
            'ingreso_mt' => 500,
            'estado' => 'pendiente',
        ]);
        $aforo->update(['id_prefactura' => $prefactura->id]);

        $this->actingAs($this->usuarioComercial())
            ->post(route('prefacturas.facturar', $prefactura))
            ->assertRedirect();

        $factura = Factura::first();
        $this->assertNotNull($factura);
        $this->assertSame($cliente->id, $factura->id_cliente);
        $this->assertSame(500.0, (float) $factura->ingreso_mt);
        $this->assertDatabaseHas('aforos', ['id' => $aforo->id, 'id_factura' => $factura->id, 'id_prefactura' => $prefactura->id]);
        $this->assertDatabaseHas('prefacturas', ['id' => $prefactura->id, 'estado' => 'procesada']);
    }

    public function test_no_se_puede_facturar_prefactura_no_pendiente(): void
    {
        $cliente = $this->cliente();
        $prefactura = Prefactura::create([
            'numero' => 'PF-2026-0002',
            'id_cliente' => $cliente->id,
            'fecha' => now()->toDateString(),
            'flete_mt' => 0,
            'flete_mlc' => 0,
            'flete_demora' => 0,
            'otros_mt' => 0,
            'ingreso_mt' => 0,
            'estado' => 'procesada',
        ]);

        $this->actingAs($this->usuarioComercial())
            ->post(route('prefacturas.facturar', $prefactura))
            ->assertSessionHasErrors('error');

        $this->assertDatabaseCount('facturas', 0);
    }

    public function test_aforo_prefacturado_no_es_facturable_directamente(): void
    {
        $cliente = $this->cliente();
        $aforo = $this->aforoPendiente(500);
        $prefactura = Prefactura::create([
            'numero' => 'PF-2026-0003',
            'id_cliente' => $cliente->id,
            'fecha' => now()->toDateString(),
            'flete_mt' => 500,
            'flete_mlc' => 0,
            'flete_demora' => 0,
            'otros_mt' => 0,
            'ingreso_mt' => 500,
            'estado' => 'pendiente',
        ]);
        $aforo->update(['id_prefactura' => $prefactura->id]);

        $user = $this->usuarioComercial();

        // No aparece entre los pendientes de facturación directa
        $this->actingAs($user)
            ->get(route('facturas.create'))
            ->assertInertia(fn ($assert) => $assert->where('aforos_pendientes', []));

        // Intentar facturarlo directamente no le asigna id_factura
        $this->actingAs($user)
            ->post(route('facturas.store'), [
                'fecha_emision' => now()->toDateString(),
                'id_cliente' => $cliente->id,
                'flete_mt' => 500,
                'flete_mlc' => 0,
                'flete_demora' => 0,
                'otros_mt' => 0,
                'ingreso_mt' => 500,
                'aforos_ids' => [$aforo->id],
            ])
            ->assertRedirect(route('facturas.index'));

        // El aforo prefacturado conserva su prefactura y NO recibe factura directa
        $this->assertNull($aforo->fresh()->id_factura);
        $this->assertSame($prefactura->id, $aforo->fresh()->id_prefactura);
    }
}
