<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoClasificacionLaboral extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'designado',
        'cuadro',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
