<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('perfil.{perfil}', function ($user, string $perfil) {
    // SUPERADMIN accede a todos los canales de perfil.
    if ($user->hasRole('SUPERADMIN')) {
        return true;
    }

    // Si un SUPERADMIN está emulando este perfil activo.
    if (session('perfil_activo') === $perfil) {
        return true;
    }

    // Usuarios con el rol correspondiente.
    return $user->hasRole($perfil);
});

Broadcast::channel('kpis', function ($user) {
    return $user !== null;
});

Broadcast::channel('pizarra', function ($user) {
    return $user !== null;
});

Broadcast::channel('test', function ($user) {
    return $user !== null;
});
