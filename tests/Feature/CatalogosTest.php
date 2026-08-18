<?php

namespace Tests\Feature;

use App\Models\CatalogoItem;
use App\Models\CatalogoTipo;
use App\Models\User;
use App\Support\CatalogoSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Módulo Catálogo Unificado (catalogo_items + catalogo_tipos).
 *
 * Cubre el comportamiento schema-driven (los campos extra de cada tipo se
 * leen de `catalogo_tipos.fields`, no de código) y el CRUD del catálogo
 * unificado (`catalogo/{tipo}`). Se usa `tipos_estados` como representante
 * por tener campos extra (imagen, siglas).
 */
class CatalogosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        CatalogoSchema::flushCache();
    }

    /** Usuario con permisos del catálogo unificado (rol CONFIGURACIONES). */
    private function usuarioCatalogo(): User
    {
        $user = User::factory()->create();
        $user->assignRole('CONFIGURACIONES');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    private function usuarioSinPermiso(): User
    {
        $user = User::factory()->create();
        $user->assignRole('OPERATIVOS');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_catalogo_index_renderiza_pagina_unificada(): void
    {
        CatalogoItem::create(['tipo' => 'tipos_estados', 'codigo' => '01', 'nombre' => 'Activo']);

        $this->actingAs($this->usuarioCatalogo())
            ->get(route('catalogo.index', 'tipos_estados'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Catalogo/Index')
                    ->has('items.data', 1)
                    ->has('catalogConfig')
                    ->where('catalogConfig.tipo', 'tipos_estados')
            );
    }

    public function test_catalogo_index_filtra_por_busqueda(): void
    {
        CatalogoItem::create(['tipo' => 'tipos_estados', 'codigo' => '01', 'nombre' => 'Activo']);
        CatalogoItem::create(['tipo' => 'tipos_estados', 'codigo' => '02', 'nombre' => 'Malo']);

        $this->actingAs($this->usuarioCatalogo())
            ->get(route('catalogo.index', ['tipo' => 'tipos_estados', 'search' => 'Malo']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('items.data', 1)->where('items.data.0.nombre', 'Malo'));
    }

    public function test_schema_driven_lee_campos_extra_desde_la_bd(): void
    {
        // El seeder puebla `catalogo_tipos.fields` para tipos_estados.
        $campos = CatalogoSchema::extraFields('tipos_estados');

        $this->assertArrayHasKey('imagen', $campos);
        $this->assertSame('Siglas', $campos['siglas']['label']);

        // Al editar `fields` en la BD el esquema cambia sin tocar código.
        // Se usa el modelo (cast `array`) para garantizar un solo json_encode.
        $tipo = CatalogoTipo::where('tipo', 'tipos_estados')->firstOrFail();
        $tipo->fields = ['nuevo_campo' => ['label' => 'Campo Nuevo', 'type' => 'text']];
        $tipo->save();
        CatalogoSchema::flushCache();

        $this->assertSame(['nuevo_campo'], array_keys(CatalogoSchema::extraFields('tipos_estados')));
    }

    public function test_catalogo_store_crea_con_extras_schema_driven(): void
    {
        $this->actingAs($this->usuarioCatalogo())
            ->post(route('catalogo.store', 'tipos_estados'), [
                'nombre' => 'Activo',
                'activo' => true,
                'imagen' => 'activo.png',
                'siglas' => 'AC',
            ])
            ->assertRedirect();

        $item = CatalogoItem::where('tipo', 'tipos_estados')->where('nombre', 'Activo')->firstOrFail();

        $this->assertSame('AC', $item->extra['siglas']);
        $this->assertSame('activo.png', $item->extra['imagen']);
        $this->assertNotNull($item->codigo); // código auto-generado
    }

    public function test_catalogo_update_edita_nombre_y_extras(): void
    {
        $item = CatalogoItem::create([
            'tipo' => 'tipos_estados', 'codigo' => '01', 'nombre' => 'Activo',
            'extra' => ['imagen' => 'a.png', 'siglas' => 'AC'],
        ]);

        $this->actingAs($this->usuarioCatalogo())
            ->put(route('catalogo.update', ['tipo' => 'tipos_estados', 'id' => $item->id]), [
                'nombre' => 'Activo editado',
                'activo' => true,
                'imagen' => 'a2.png',
                'siglas' => 'AC2',
            ])
            ->assertRedirect();

        $item->refresh();

        $this->assertSame('Activo editado', $item->nombre);
        $this->assertSame('AC2', $item->extra['siglas']);
        $this->assertSame('a2.png', $item->extra['imagen']);
    }

    public function test_catalogo_destroy_elimina(): void
    {
        $item = CatalogoItem::create(['tipo' => 'tipos_estados', 'codigo' => '01', 'nombre' => 'Activo']);

        $this->actingAs($this->usuarioCatalogo())
            ->delete(route('catalogo.destroy', ['tipo' => 'tipos_estados', 'id' => $item->id]))
            ->assertRedirect();

        $this->assertSoftDeleted('catalogo_items', ['id' => $item->id]);
    }

    public function test_catalogo_valida_nombre_requerido(): void
    {
        $this->actingAs($this->usuarioCatalogo())
            ->post(route('catalogo.store', 'tipos_estados'), ['activo' => true])
            ->assertSessionHasErrors('nombre');
    }

    public function test_catalogo_cerrado_para_perfil_sin_permiso(): void
    {
        $this->actingAs($this->usuarioSinPermiso())
            ->get(route('catalogo.index', 'tipos_estados'))
            ->assertForbidden();

        $this->actingAs($this->usuarioSinPermiso())
            ->post(route('catalogo.store', 'tipos_estados'), ['nombre' => 'No'])
            ->assertForbidden();

        $this->assertDatabaseMissing('catalogo_items', ['nombre' => 'No']);
    }

    public function test_catalogo_tipos_lista_grupos_activos(): void
    {
        $this->actingAs($this->usuarioCatalogo())
            ->get(route('catalogo.tipos'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Catalogo/Tipos')
                    ->has('grupos')
            );
    }

    public function test_catalogo_update_tipo_permite_editar_fields(): void
    {
        $this->actingAs($this->usuarioCatalogo())
            ->put(route('catalogo.update-tipo', 'tipos_estados'), [
                'agrupacion' => 'RRHH',
                'activo' => true,
                'fields' => ['x' => ['label' => 'X', 'type' => 'text']],
            ])
            ->assertRedirect();

        $tipo = CatalogoTipo::where('tipo', 'tipos_estados')->firstOrFail();
        $this->assertSame(['x'], array_keys($tipo->fields ?? []));
    }
}
