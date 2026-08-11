<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(PermissionSeeder::class);
        $this->cargarMenuBackup();
    }

    private function cargarMenuBackup(): void
    {
        $path = database_path('menu_items_backup_2026-08-06.json');
        $items = json_decode(file_get_contents($path), true);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('menu_items')->delete();
        DB::table('menu_items')->insert(array_map(
            fn (array $i) => [
                'id' => $i['id'],
                'parent_id' => $i['parent_id'],
                'label' => $i['label'],
                'icon' => $i['icon'] ?? null,
                'route' => $i['route'] ?? null,
                'permission' => $i['permission'] ?? null,
                'orden' => $i['orden'],
                'activo' => $i['activo'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $items
        ));
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
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
                ->has('menu', 6)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Folios')
                ->where('menu.2.label', 'Catálogos')
                ->where('menu.2.children.0.label', 'Marcas')
                ->where('menu.3.label', 'Flota')
                ->where('menu.3.children.0.label', 'Vehículos')
                ->where('menu.4.label', 'Taller')
                ->where('menu.5.label', 'Reportes')
                ->where('menu.5.children.0.label', 'Listado de Marcas')
        );
    }

    public function test_admin_ve_todos_los_modulos_incluyendo_comercial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SUPERADMIN');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 10)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Administración')
                ->where('menu.1.children.0.label', 'Entidades')
                ->where('menu.1.children.1.label', 'Usuarios')
                ->where('menu.2.label', 'Catálogos')
                ->where('menu.2.children.0.label', 'Marcas')
                ->where('menu.3.label', 'Flota')
                ->where('menu.3.children.0.label', 'Vehículos')
                ->where('menu.4.label', 'Taller')
                ->where('menu.5.label', 'Comercial')
                ->where('menu.5.children.0.label', 'Clientes')
                ->where('menu.6.label', 'Facturación')
                ->where('menu.6.children.0.label', 'Facturas')
                ->where('menu.7.label', 'RRHH')
                ->where('menu.7.children.0.label', 'Bolsa')
                ->where('menu.8.label', 'Contabilidad')
                ->where('menu.8.children.0.label', 'Conciliaciones')
                ->where('menu.9.label', 'Reportes')
                ->where('menu.9.children.0.label', 'Listado de Marcas')
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
                ->where('menu.1.label', 'Catálogos')
                ->where('menu.1.children.0.label', 'Incidencias')
                ->where('menu.2.label', 'RRHH')
                ->where('menu.2.children.0.label', 'Bolsa')
        );
    }

    public function test_comercial_ve_modulo_comercial(): void
    {
        $user = User::factory()->create();
        $user->assignRole('COMERCIAL');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn (Assert $page) => $page
                ->has('menu', 5)
                ->where('menu.0.label', 'Dashboard')
                ->where('menu.1.label', 'Administración')
                ->where('menu.1.children.0.label', 'Tipos de Modelo')
                ->where('menu.2.label', 'Catálogos')
                ->where('menu.3.label', 'Comercial')
                ->where('menu.3.children.0.label', 'Clientes')
                ->where('menu.4.label', 'Facturación')
                ->where('menu.4.children.0.label', 'Facturas')
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
                ->where('menu.1.children.0.label', 'Conciliaciones')
        );
    }
}
