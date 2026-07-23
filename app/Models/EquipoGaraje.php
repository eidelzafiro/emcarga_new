<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoGaraje extends Model
{
    protected $table = 'equipos_garaje';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
