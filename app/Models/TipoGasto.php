<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoGasto extends Model
{
    protected $table = 'tipos_gastos';

    protected $fillable = ['codigo', 'nombre', 'tipo', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
