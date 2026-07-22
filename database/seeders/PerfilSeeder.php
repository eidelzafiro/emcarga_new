<?php

namespace Database\Seeders;

use App\Models\Perfil;
use Illuminate\Database\Seeder;

class PerfilSeeder extends Seeder
{
    /**
     * Perfiles heredados del sistema legacy (rh_perfiles).
     */
    public function run(): void
    {
        $perfiles = [
            ['nombre' => 'RECHUM', 'descripcion' => 'Recursos Humanos'],
            ['nombre' => 'TECNICA', 'descripcion' => 'Área Técnica y Taller'],
            ['nombre' => 'COMERCIAL', 'descripcion' => 'Área Comercial'],
            ['nombre' => 'CONTABILIDAD', 'descripcion' => 'Contabilidad y Costos'],
            ['nombre' => 'ADMIN', 'descripcion' => 'Administrador del Sistema'],
            ['nombre' => 'OPERATIVOS', 'descripcion' => 'Operaciones de Transporte'],
        ];

        foreach ($perfiles as $perfil) {
            Perfil::firstOrCreate(['nombre' => $perfil['nombre']], $perfil);
        }
    }
}
