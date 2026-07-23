<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoMedioProteccion extends Model
{
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
