<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DescuentosEmpleado extends Model
{
    protected $table = 'descuentos_empleados';

    protected $fillable = ['id_empleado', 'fecha_inicio', 'tiempo', 'motivo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
