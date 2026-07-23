<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIncidencia extends Model
{
    protected $table = 'tipos_incidencias';

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
