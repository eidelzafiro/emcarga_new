<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
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
