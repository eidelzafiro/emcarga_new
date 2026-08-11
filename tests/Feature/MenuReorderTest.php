<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MenuReorderTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(PermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('SUPERADMIN');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('menu_items')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function crearItem(int $id, ?int $parentId, int $orden, string $label): void
    {
        DB::table('menu_items')->insert([
            'id' => $id,
            'parent_id' => $parentId,
            'label' => $label,
            'icon' => null,
            'route' => null,
            'permission' => null,
            'orden' => $orden,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_reordenar_mueve_item_a_otro_agrupador(): void
    {
        $this->crearItem(1, null, 1, 'Dashboard');
        $this->crearItem(93, null, 2, 'Flota');
        $this->crearItem(3, 93, 1, 'Vehículos');
        $this->crearItem(67, null, 3, 'Reportes');

        $tree = [
            ['id' => 1, 'parent_id' => null, 'children' => []],
            ['id' => 93, 'parent_id' => null, 'children' => [
                ['id' => 3, 'parent_id' => 93, 'children' => []],
            ]],
            ['id' => 67, 'parent_id' => null, 'children' => []],
        ];

        $this->actingAs($this->user)
            ->post(route('menu-items.reordenar'), ['tree' => $tree])
            ->assertSessionHas('success');

        $vehiculos = MenuItem::find(3);
        $this->assertEquals(93, $vehiculos->parent_id);
        $this->assertEquals(1, $vehiculos->orden);
    }

    public function test_reordenar_respeta_orden_dentro_de_agrupador(): void
    {
        $this->crearItem(1, null, 1, 'Dashboard');
        $this->crearItem(2, null, 2, 'Comercial');
        $this->crearItem(4, 2, 1, 'Clientes');
        $this->crearItem(3, 2, 2, 'Vehículos');
        $this->crearItem(67, null, 3, 'Reportes');

        $tree = [
            ['id' => 1, 'parent_id' => null, 'children' => []],
            ['id' => 2, 'parent_id' => null, 'children' => [
                ['id' => 4, 'parent_id' => 2, 'children' => []],
                ['id' => 3, 'parent_id' => 2, 'children' => []],
            ]],
            ['id' => 67, 'parent_id' => null, 'children' => []],
        ];

        $this->actingAs($this->user)
            ->post(route('menu-items.reordenar'), ['tree' => $tree])
            ->assertSessionHas('success');

        $this->assertEquals(1, MenuItem::find(4)->orden);
        $this->assertEquals(2, MenuItem::find(3)->orden);
    }
}
