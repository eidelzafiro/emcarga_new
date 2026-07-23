<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiposJefeGrupo extends Model
{
    protected $table = 'tipos_jefe_grupo';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
