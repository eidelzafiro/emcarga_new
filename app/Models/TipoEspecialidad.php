<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEspecialidad extends Model
{
    protected $table = 'tipos_especialidad';

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
