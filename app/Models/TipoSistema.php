<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSistema extends Model
{
    protected $table = 'tipos_sistemas';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
