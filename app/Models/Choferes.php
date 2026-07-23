<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Choferes extends Model
{
    use SoftDeletes;

    protected $table = 'choferes';

    protected $fillable = ['codigo', 'nombre', 'ci', 'id_tractivo', 'id_empleado', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'deleted_at' => 'datetime'];
    }
}
