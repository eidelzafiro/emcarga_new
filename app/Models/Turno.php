<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Turno extends Model
{
    use SoftDeletes;

    protected $table = 'turnos';

    protected $fillable = [
        'codigo',
        'nombre',
        'hora_entrada',
        'hora_salida',
        'dias_descanso',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
