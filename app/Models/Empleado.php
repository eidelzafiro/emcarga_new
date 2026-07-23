<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use SoftDeletes;

    protected $table = 'empleados';

    protected $fillable = ['codigo', 'nombre', 'expediente', 'id_area', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'deleted_at' => 'datetime'];
    }
}
