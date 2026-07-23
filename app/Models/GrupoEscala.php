<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoEscala extends Model
{
    protected $table = 'grupos_escala';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
