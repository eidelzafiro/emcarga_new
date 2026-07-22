<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquiposElectrico extends Model
{
    protected $table = 'equipos_electricos';

    protected $fillable = ['codigo', 'nombre', 'tipo', 'potencia', 'unidad', 'activo'];

    protected function casts(): array
    {
        return [
            'potencia' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }
}
