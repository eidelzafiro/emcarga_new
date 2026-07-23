<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCalificador extends Model
{
    protected $table = 'tipos_calificadores';

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
