<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\NotificacionSistema;
use Illuminate\Console\Command;

class Notificar extends Command
{
    protected $signature = 'emcarga:notificar {--user= : ID de usuario} {--todos : Enviar a todos los usuarios}';

    protected $description = 'Envía una notificación de prueba a un usuario';

    public function handle()
    {
        $titulo = $this->anticipate('Título', ['Prueba', 'Notificación de prueba']);
        $cuerpo = $this->ask('Mensaje') ?: 'Mensaje de prueba';

        $usuarios = collect();
        if ($this->option('todos')) {
            $usuarios = User::all();
        } elseif ($userId = $this->option('user')) {
            $usuarios = User::where('id', $userId)->get();
        } else {
            $usuarios = User::whereHas('roles', fn ($q) => $q->where('name', 'ADMIN'))->get();
        }

        $bar = $this->output->createProgressBar($usuarios->count());
        $bar->start();

        foreach ($usuarios as $user) {
            $user->notify(new NotificacionSistema(
                titulo: $titulo,
                cuerpo: $cuerpo,
                tipo: 'info',
            ));
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Notificación enviada a {$usuarios->count()} usuario(s).");
    }
}
