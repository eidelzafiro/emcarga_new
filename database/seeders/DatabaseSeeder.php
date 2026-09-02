<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CatalogoTipoSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            MenuItemSeeder::class,
            NotificacionSeeder::class,
            DestinoAgregadoSeeder::class,
            PosicionNeumaticoSeeder::class,
        ]);
    }
}
