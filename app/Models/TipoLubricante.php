<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoLubricante extends Model
{
    protected $table = 'tipos_lubricantes';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
