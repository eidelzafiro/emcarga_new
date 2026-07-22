<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Usuario administrador inicial para desarrollo.
     * Entra con contraseña temporal y el sistema le exige cambiarla
     * en el primer acceso (equivalente al 'ZAFIRO' del legacy).
     */
    public function run(): void
    {
        $perfilAdmin = Perfil::where('nombre', 'ADMIN')->first();

        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador del Sistema',
                'password' => Hash::make('admin123'),
                'idperfil' => $perfilAdmin?->id,
                'password_temporal' => true,
            ]
        );
    }
}
