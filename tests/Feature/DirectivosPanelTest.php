<?php

namespace Tests\Feature;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Perfil DIRECTIVOS: panel con los dashboards de los módulos operativos en
 * pestañas y acceso a todos los reportes, sin RRHH ni administración.
 */
class DirectivosPanelTest extends TestCase
{
    private function usuarioDirectivos(): User
    {
        $user = User::factory()->create();
        $user->assignRole('DIRECTIVOS');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    public function test_dashboard_renderiza_el_panel_de_directivos(): void
    {
        $this->actingAs($this->usuarioDirectivos())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Directivos/Panel'));
    }

    public function test_modulos_operativos_embebibles(): void
    {
        $user = $this->usuarioDirectivos();

        foreach (['tecnica', 'operativos', 'comercial', 'contabilidad'] as $modulo) {
            $this->actingAs($user)
                ->get("/dashboard/modulo/{$modulo}")
                ->assertOk();
        }
    }

    public function test_modulo_no_operativo_devuelve_404(): void
    {
        $this->actingAs($this->usuarioDirectivos())
            ->get('/dashboard/modulo/rrhh')
            ->assertNotFound();
    }

    public function test_tiene_todos_los_reportes_de_lectura(): void
    {
        $user = $this->usuarioDirectivos();

        foreach ([
            'reportes.ver', 'reportes.generar', 'reportes-nomina.ver',
            'reportes-ingresos.ver', 'reportes-tecnico.ver',
            'reportes-combustible.ver', 'reportes-facturacion.ver',
        ] as $permiso) {
            $this->assertTrue($user->can($permiso), "DIRECTIVOS debería tener {$permiso}");
        }
    }

    public function test_no_tiene_permisos_de_escritura_ni_rrhh(): void
    {
        $user = $this->usuarioDirectivos();

        $this->assertFalse($user->can('tractivos.crear'));
        $this->assertFalse($user->can('facturas.eliminar'));
        $this->assertFalse($user->can('usuarios.ver'));
        $this->assertFalse($user->can('bolsa.ver'));
    }

    public function test_menu_sin_rrhh_ni_administracion(): void
    {
        $resp = $this->actingAs($this->usuarioDirectivos())->get('/dashboard');

        $menu = $resp->viewData('page')['props']['menu'] ?? [];

        $labels = [];
        $acumular = function (array $items) use (&$acumular, &$labels): void {
            foreach ($items as $item) {
                $labels[] = $item['label'];
                $acumular($item['children'] ?? []);
            }
        };
        $acumular($menu);

        // No debe ver RRHH ni los módulos de administración de usuarios/entidades.
        $this->assertNotContains('RRHH', $labels);
        $this->assertNotContains('Usuarios', $labels);
        $this->assertNotContains('Entidades', $labels);
        $this->assertNotContains('Perfiles', $labels);
        $this->assertNotContains('Menús', $labels);
        $this->assertContains('Dashboard', $labels);
    }

    public function test_superadmin_puede_emular_directivos_desde_el_combo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('SUPERADMIN');
        $admin->password_temporal = false;
        $admin->save();

        // El combo de perfiles incluye DIRECTIVOS.
        $this->actingAs($admin)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page->where(
                'contexto.perfiles',
                fn ($perfiles) => collect($perfiles)->contains('DIRECTIVOS')
            )
        );

        // Y se puede cambiar a ese perfil.
        $this->actingAs($admin)
            ->post(route('contexto.perfil'), ['perfil' => 'DIRECTIVOS'])
            ->assertRedirect();

        $this->assertSame('DIRECTIVOS', session('perfil_activo'));
    }

    public function test_vista_unificada_de_reportes(): void
    {
        $this->actingAs($this->usuarioDirectivos())
            ->get(route('reportes.directivos'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Directivos/Reportes'));
    }
}
