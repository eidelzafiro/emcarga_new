<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiposOperacione extends Model
{
    use SoftDeletes;

    protected $table = 'tipos_operaciones';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
