<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEstado extends Model
{
    protected $table = 'tipos_estados';

    protected $fillable = ['codigo', 'nombre', 'imagen', 'siglas', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
