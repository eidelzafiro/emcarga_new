<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccioneHotkey extends Model
{
    protected $table = 'acciones_hotkeys';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
