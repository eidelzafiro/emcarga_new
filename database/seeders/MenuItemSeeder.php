<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        MenuItem::firstOrCreate(
            ['route' => 'dashboard'],
            ['label' => 'Dashboard', 'icon' => 'home', 'permission' => 'dashboard.ver', 'orden' => 1]
        );

        MenuItem::firstOrCreate(
            ['route' => 'pizarra.index'],
            ['label' => 'Pizarra', 'icon' => 'map', 'permission' => 'pizarra.ver', 'orden' => 2]
        );

        $flota = MenuItem::firstOrCreate(
            ['label' => 'Flota', 'parent_id' => null],
            ['icon' => 'truck', 'route' => null, 'permission' => null, 'orden' => 3]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tractivos.index'],
            ['label' => 'Vehículos', 'icon' => 'truck', 'permission' => 'tractivos.ver', 'orden' => 1, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'motores.index'],
            ['label' => 'Motores', 'icon' => 'cog', 'permission' => 'motores.ver', 'orden' => 2, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'cajas.index'],
            ['label' => 'Cajas', 'icon' => 'cog', 'permission' => 'cajas.ver', 'orden' => 3, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'diferenciales.index'],
            ['label' => 'Diferenciales', 'icon' => 'cog', 'permission' => 'diferenciales.ver', 'orden' => 4, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'baterias.index'],
            ['label' => 'Baterías', 'icon' => 'bolt', 'permission' => 'baterias.ver', 'orden' => 5, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'neumaticos.index'],
            ['label' => 'Neumáticos', 'icon' => 'cog', 'permission' => 'neumaticos.ver', 'orden' => 6, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'lubricantes.index'],
            ['label' => 'Lubricantes', 'icon' => 'droplet', 'permission' => 'lubricantes.ver', 'orden' => 7, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'otros-agregados.index'],
            ['label' => 'Otros Agregados', 'icon' => 'cog', 'permission' => 'otros-agregados.ver', 'orden' => 8, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'energia.index'],
            ['label' => 'Energía', 'icon' => 'bolt', 'permission' => 'energia.ver', 'orden' => 9, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'taller.index'],
            ['label' => 'Taller', 'icon' => 'wrench', 'permission' => 'taller.ver', 'orden' => 4]
        );

        $comercial = MenuItem::firstOrCreate(
            ['label' => 'Comercial', 'parent_id' => null],
            ['icon' => 'briefcase', 'route' => null, 'permission' => null, 'orden' => 5]
        );

        MenuItem::firstOrCreate(
            ['route' => 'clientes.index'],
            ['label' => 'Clientes', 'icon' => 'users', 'permission' => 'clientes.ver', 'orden' => 1, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'lugares.index'],
            ['label' => 'Lugares', 'icon' => 'map-marker', 'permission' => 'lugares.ver', 'orden' => 2, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'distancias.index'],
            ['label' => 'Distancias', 'icon' => 'arrows-alt', 'permission' => 'distancias.ver', 'orden' => 3, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'acuerdos.index'],
            ['label' => 'Acuerdos', 'icon' => 'file-invoice', 'permission' => 'acuerdos.ver', 'orden' => 4, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'solicitudes.index'],
            ['label' => 'Solicitudes', 'icon' => 'envelope', 'permission' => 'solicitudes.ver', 'orden' => 5, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'giros.index'],
            ['label' => 'Cartas Porte', 'icon' => 'file', 'permission' => 'giros.ver', 'orden' => 6, 'parent_id' => $comercial->id]
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
