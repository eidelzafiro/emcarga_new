<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPlantilla extends Model
{
    protected $table = 'tipos_plantillas';

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
