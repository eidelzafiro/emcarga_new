<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoVehiculo extends Model
{
    protected $table = 'tipos_vehiculos';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
