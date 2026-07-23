<?php

namespace Tests\Feature;

use App\Models\TipoSexo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Catálogos (Fase 5.7): comportamiento del trait ManagesCatalog
 * y la página genérica Catalogo/Index compartida por los catálogos
 * código/nombre. Se usa tipos-sexo como representante.
 */
class CatalogosTest extends TestCase
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

    public function test_index_renderiza_pagina_generica_de_catalogo(): void
    {
        TipoSexo::create(['codigo' => 'M', 'nombre' => 'Masculino']);

        $this->actingAs($this->usuarioRechum())
            ->get(route('tipos-sexo.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Catalogo/Index')
                    ->has('items')
                    ->has('catalogConfig')
            );
    }

    public function test_index_filtra_por_busqueda(): void
    {
        TipoSexo::create(['codigo' => 'M', 'nombre' => 'Masculino']);
        TipoSexo::create(['codigo' => 'F', 'nombre' => 'Femenino']);

        $this->actingAs($this->usuarioRechum())
            ->get(route('tipos-sexo.index', ['search' => 'Femenino']))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->has('items.data', 1)
                    ->where('items.data.0.nombre', 'Femenino')
            );
    }

    public function test_crud_completo(): void
    {
        $user = $this->usuarioRechum();

        // Crear
        $this->actingAs($user)
            ->post(route('tipos-sexo.store'), ['codigo' => 'X', 'nombre' => 'Otro', 'activo' => true])
            ->assertRedirect();

        $item = TipoSexo::where('codigo', 'X')->firstOrFail();

        // Editar
        $this->actingAs($user)
            ->put(route('tipos-sexo.update', $item), ['codigo' => 'X', 'nombre' => 'Otro editado', 'activo' => true])
            ->assertRedirect();

        $this->assertSame('Otro editado', $item->fresh()->nombre);

        // Eliminar
        $this->actingAs($user)
            ->delete(route('tipos-sexo.destroy', $item))
            ->assertRedirect();

        $this->assertDatabaseMissing('tipos_sexo', ['codigo' => 'X']);
    }

    public function test_valida_codigo_y_nombre_requeridos(): void
    {
        $this->actingAs($this->usuarioRechum())
            ->post(route('tipos-sexo.store'), [])
            ->assertSessionHasErrors(['codigo', 'nombre']);
    }

    public function test_valida_codigo_unico(): void
    {
        TipoSexo::create(['codigo' => 'M', 'nombre' => 'Masculino']);

        $this->actingAs($this->usuarioRechum())
            ->post(route('tipos-sexo.store'), ['codigo' => 'M', 'nombre' => 'Duplicado'])
            ->assertSessionHasErrors('codigo');
    }

    public function test_catalogo_cerrado_para_otros_perfiles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('CONTABILIDAD');
        $user->password_temporal = false;
        $user->save();

        $this->actingAs($user)->get(route('tipos-sexo.index'))->assertForbidden();

        $this->actingAs($user)
            ->post(route('tipos-sexo.store'), ['codigo' => 'X', 'nombre' => 'No'])
            ->assertForbidden();

        $this->assertDatabaseMissing('tipos_sexo', ['codigo' => 'X']);
    }
}
