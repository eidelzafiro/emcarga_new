<?php

namespace Tests\Feature;

use App\Models\TipoContrato;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo RRHH (Fase 5.5): bolsa, plantilla, movimientos, salarios
 * y catálogos de tipos asociados.
 */
class RrhhTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function usuarioRechum(): User
    {
        $user = User::factory()->create();
        $user->assignRole('RECHUM');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_bolsa_index(): void
    {
        $this->actingAs($this->usuarioRechum())
            ->get(route('bolsa.index'))
            ->assertOk();
    }

    public function test_plantilla_index(): void
    {
        $this->actingAs($this->usuarioRechum())
            ->get(route('plantilla.index'))
            ->assertOk();
    }

    public function test_historial_movimientos_index(): void
    {
        $this->actingAs($this->usuarioRechum())
            ->get(route('historial-movimientos.index'))
            ->assertOk();
    }

    public function test_catalogos_rrhh_index(): void
    {
        $rutas = [
            'tipos-incidencias.index',
            'tipos-penalizaciones.index',
            'tipos-contratos.index',
            'tipos-sistemas-pago.index',
            'tipos-pagos-adicionales.index',
            'tipos-tasas.index',
            'salarios.index',
            'salarios-administrativos.index',
            'provincias.index',
            'municipios.index',
        ];

        $user = $this->usuarioRechum();

        foreach ($rutas as $ruta) {
            $this->actingAs($user)
                ->get(route($ruta))
                ->assertOk("La ruta {$ruta} debería ser accesible para RECHUM");
        }
    }

    public function test_crud_tipos_contratos(): void
    {
        $user = $this->usuarioRechum();

        // Crear
        $this->actingAs($user)
            ->post(route('tipos-contratos.store'), [
                'codigo' => 'IND',
                'nombre' => 'Indeterminado',
                'activo' => true,
            ])
            ->assertRedirect();

        $tipo = TipoContrato::where('codigo', 'IND')->firstOrFail();

        // Editar
        $this->actingAs($user)
            ->put(route('tipos-contratos.update', $tipo), [
                'codigo' => 'IND',
                'nombre' => 'Indeterminado actualizado',
                'activo' => true,
            ])
            ->assertRedirect();

        $this->assertSame('Indeterminado actualizado', $tipo->fresh()->nombre);

        // Eliminar
        $this->actingAs($user)
            ->delete(route('tipos-contratos.destroy', $tipo))
            ->assertRedirect();

        $this->assertDatabaseMissing('tipos_contratos', ['codigo' => 'IND']);
    }

    public function test_codigo_de_tipo_contrato_es_unico(): void
    {
        TipoContrato::create(['codigo' => 'DET', 'nombre' => 'Determinado']);

        $this->actingAs($this->usuarioRechum())
            ->post(route('tipos-contratos.store'), [
                'codigo' => 'DET',
                'nombre' => 'Duplicado',
            ])
            ->assertSessionHasErrors('codigo');
    }

    public function test_modulo_cerrado_para_otros_perfiles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('CONTABILIDAD');
        $user->password_temporal = false;
        $user->save();

        $this->actingAs($user)->get(route('bolsa.index'))->assertForbidden();
        $this->actingAs($user)->get(route('plantilla.index'))->assertForbidden();
    }
}
