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
                ->has('menu', 5)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Pizarra')
                ->where('menu.2.label', 'Flota')
                ->where('menu.3.label', 'Taller')
                ->where('menu.4.label', 'Catálogos')
                ->where('menu.4.children.0.label', 'Marcas')
        );
    }

    public function test_admin_ve_todos_los_modulos_incluyendo_comercial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 10)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.4.label', 'Comercial')
                ->where('menu.4.children.0.label', 'Clientes')
                ->where('menu.5.label', 'Facturación')
                ->where('menu.5.children.0.label', 'Facturas')
                ->where('menu.6.label', 'RRHH')
                ->where('menu.6.children.0.label', 'Bolsa')
                ->where('menu.7.label', 'Contabilidad')
                ->where('menu.7.children.0.label', 'Conciliaciones')
                ->where('menu.8.label', 'Catálogos')
                ->where('menu.8.children.0.label', 'Marcas')
                ->where('menu.9.label', 'Administración')
                ->where('menu.9.children.0.label', 'Usuarios')
                ->where('menu.9.children.1.label', 'Perfiles')
        );
    }

    public function test_rechum_ve_rrhh(): void
    {
        $user = User::factory()->create();
        $user->assignRole('RECHUM');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 3)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'RRHH')
                ->where('menu.2.label', 'Catálogos')
                ->where('menu.2.children.0.label', 'Organismos')
        );
    }

    public function test_comercial_ve_modulo_comercial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('COMERCIAL');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 4)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Comercial')
                ->where('menu.2.label', 'Facturación')
                ->where('menu.3.label', 'Catálogos')
        );
    }

    public function test_contabilidad_ve_su_modulo(): void
    {
        $user = User::factory()->create();
        $user->assignRole('CONTABILIDAD');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 2)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Contabilidad')
        );
    }
}
