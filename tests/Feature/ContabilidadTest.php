<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Contabilidad (Fase 5.6): conciliaciones, combustible,
 * tarjetas, inventario, vales y otros gastos.
 */
class ContabilidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function usuarioContabilidad(): User
    {
        $user = User::factory()->create();
        $user->assignRole('CONTABILIDAD');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_conciliaciones_index(): void
    {
        $this->actingAs($this->usuarioContabilidad())
            ->get(route('conciliaciones.index'))
            ->assertOk();
    }

    public function test_combustible_cargas_index(): void
    {
        $this->actingAs($this->usuarioContabilidad())
            ->get(route('combustible-cargas.index'))
            ->assertOk();
    }

    public function test_combustible_descargas_index(): void
    {
        $this->actingAs($this->usuarioContabilidad())
            ->get(route('combustible-descargas.index'))
            ->assertOk();
    }

    public function test_catalogos_contabilidad_index(): void
    {
        $rutas = [
            'otros-gastos.index',
            'vales.index',
            'inventario.index',
            'servicentros.index',
            'tipos-conceptos.index',
            'tipos-documentos.index',
            'estados-tarjetas.index',
            'elementos-gasto.index',
        ];

        $user = $this->usuarioContabilidad();

        foreach ($rutas as $ruta) {
            $this->actingAs($user)
                ->get(route($ruta))
                ->assertOk("La ruta {$ruta} debería ser accesible para CONTABILIDAD");
        }
    }

    public function test_modulo_cerrado_para_otros_perfiles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('RECHUM');
        $user->password_temporal = false;
        $user->save();

        $this->actingAs($user)->get(route('conciliaciones.index'))->assertForbidden();
        $this->actingAs($user)->get(route('combustible-cargas.index'))->assertForbidden();
    }
}
