<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\NotificacionSistema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NotificacionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'ADMIN'))->first();

        if (! $admin) {
            return;
        }

        // Inserción directa: el seeding no debe depender de Redis/Reverb
        // (notify() dispararía el broadcast y rompería migrate:fresh --seed).
        $notificacion = new NotificacionSistema(
            titulo: 'Bienvenido a EMCARGA',
            cuerpo: 'El sistema de gestión empresarial está listo. Explore los módulos disponibles.',
            tipo: 'success',
            icono: 'pi pi-check-circle',
        );

        $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => NotificacionSistema::class,
            'data' => $notificacion->toArray($admin),
        ]);
    }
}
