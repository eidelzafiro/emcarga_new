<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCombustible extends Model
{
    protected $table = 'tipos_combustibles';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
