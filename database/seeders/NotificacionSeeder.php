<?php

namespace Database\Seeders;

use App\Notifications\NotificacionSistema;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificacionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'ADMIN'))->first();

        if ($admin) {
            $admin->notify(new NotificacionSistema(
                titulo: 'Bienvenido a EMCARGA',
                cuerpo: 'El sistema de gestión empresarial está listo. Explore los módulos disponibles.',
                tipo: 'success',
                icono: 'pi pi-check-circle',
            ));
        }
    }
}
