<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Usuario administrador inicial para desarrollo.
     * Entra con contraseña temporal y el sistema le exige cambiarla
     * en el primer acceso (equivalente al 'ZAFIRO' del legacy).
     * El username se guarda en mayúsculas (paridad con el legacy).
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'ADMIN'],
            [
                'name' => 'Administrador del Sistema',
                'password' => Hash::make('admin123'),
                'password_temporal' => true,
            ]
        );

        $admin->assignRole('SUPERADMIN');
    }
}
