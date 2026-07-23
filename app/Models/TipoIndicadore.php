<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIndicadore extends Model
{
    protected $table = 'tipos_indicadores';

    protected $fillable = ['codigo', 'nombre', 'unidad', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
