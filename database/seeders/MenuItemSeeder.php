<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Ítems base del menú. Al migrar cada módulo (Fase 5) se registran
     * aquí sus ítems con el permiso que los gobierna.
     */
    public function run(): void
    {
        MenuItem::firstOrCreate(
            ['route' => 'dashboard'],
            ['label' => 'Dashboard', 'icon' => 'home', 'permission' => 'dashboard.ver', 'orden' => 1]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tractivos.index'],
            ['label' => 'Flota', 'icon' => 'truck', 'permission' => 'tractivos.ver', 'orden' => 2]
        );

        $administracion = MenuItem::firstOrCreate(
            ['label' => 'Administración', 'parent_id' => null],
            ['icon' => 'cog', 'route' => null, 'permission' => null, 'orden' => 90]
        );

        MenuItem::firstOrCreate(
            ['route' => 'usuarios.index'],
            ['label' => 'Usuarios', 'icon' => 'users', 'permission' => 'usuarios.ver', 'orden' => 1, 'parent_id' => $administracion->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'perfiles.index'],
            ['label' => 'Perfiles', 'icon' => 'shield', 'permission' => 'perfiles.ver', 'orden' => 2, 'parent_id' => $administracion->id]
        );
    }
}
