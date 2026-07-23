<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoUbicacionDefensa extends Model
{
    protected $table = 'tipos_ubicacion_defensa';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
