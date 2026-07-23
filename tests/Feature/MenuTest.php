<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MenuItemSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed([PermissionSeeder::class, MenuItemSeeder::class]);
    }

    public function test_invitado_tiene_menu_vacio(): void
    {
        $this->get('/login')->assertInertia(
            fn (Assert $page) => $page->where('menu', [])
        );
    }

    public function test_tecnica_ve_modulos_tecnicos_sin_comercial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('TECNICA');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 4)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Pizarra')
                ->where('menu.2.label', 'Flota')
                ->where('menu.3.label', 'Taller')
        );
    }

    public function test_admin_ve_todos_los_modulos_incluyendo_comercial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 8)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.4.label', 'Comercial')
                ->where('menu.4.children.0.label', 'Clientes')
                ->where('menu.5.label', 'Facturación')
                ->where('menu.5.children.0.label', 'Facturas')
                ->where('menu.6.label', 'RRHH')
                ->where('menu.6.children.0.label', 'Bolsa')
                ->where('menu.7.label', 'Administración')
                ->where('menu.7.children.0.label', 'Usuarios')
                ->where('menu.7.children.1.label', 'Perfiles')
        );
    }

    public function test_rechum_ve_rrhh(): void
    {
        $user = User::factory()->create();
        $user->assignRole('RECHUM');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 2)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'RRHH')
        );
    }

    public function test_comercial_ve_modulo_comercial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('COMERCIAL');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 3)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Comercial')
                ->where('menu.2.label', 'Facturación')
        );
    }

    public function test_agrupador_sin_hijos_visibles_no_se_muestra(): void
    {
        $user = User::factory()->create();
        $user->assignRole('CONTABILIDAD');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 1)
                ->where('menu.0.label', 'Dashboard')
        );
    }
}
