<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoNivelEducacion extends Model
{
    protected $table = 'tipos_nivel_educacion';

    protected $fillable = [
        'codigo',
        'nombre',
        'abreviatura',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
