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

    public function test_tecnica_ve_dashboard_y_flota_pero_no_administracion(): void
    {
        $user = User::factory()->create();
        $user->assignRole('TECNICA');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 2)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Flota')
        );
    }

    public function test_admin_ve_administracion_con_usuarios_y_perfiles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('ADMIN');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 3)
                ->where('menu.2.label', 'Administración')
                ->where('menu.2.children.0.label', 'Usuarios')
                ->where('menu.2.children.1.label', 'Perfiles')
        );
    }

    public function test_rechum_solo_ve_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('RECHUM');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 1)
                ->where('menu.0.label', 'Dashboard')
        );
    }

    public function test_agrupador_sin_hijos_visibles_no_se_muestra(): void
    {
        // COMERCIAL solo tiene dashboard.ver: el agrupador Administración
        // queda sin hijos visibles y no debe aparecer.
        $user = User::factory()->create();
        $user->assignRole('COMERCIAL');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 1)
                ->where('menu.0.label', 'Dashboard')
        );
    }
}
