<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = database_path('menu_items_backup_2026-07-31.json');

        if (! file_exists($ruta)) {
            $this->command?->warn('No se encontró el backup del menú en '.$ruta);

            return;
        }

        $items = json_decode(file_get_contents($ruta), true);

        if (! is_array($items)) {
            $this->command?->error('El archivo de backup del menú no es válido.');

            return;
        }

        // Padres primero (parent_id null), luego hijos
        usort($items, function ($a, $b) {
            return ($a['parent_id'] ?? 0) - ($b['parent_id'] ?? 0);
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('menu_items')->truncate();

        foreach ($items as $item) {
            DB::table('menu_items')->insert([
                'id' => $item['id'],
                'parent_id' => $item['parent_id'],
                'label' => $item['label'],
                'icon' => $item['icon'],
                'route' => $item['route'],
                'permission' => $item['permission'],
                'orden' => $item['orden'],
                'activo' => $item['activo'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command?->info('Menú restaurado desde backup: '.count($items).' ítems.');
    }
}
