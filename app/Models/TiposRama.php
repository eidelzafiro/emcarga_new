<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiposRama extends Model
{
    protected $table = 'tipos_ramas';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
