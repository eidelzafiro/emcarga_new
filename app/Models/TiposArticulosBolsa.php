<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiposArticulosBolsa extends Model
{
    protected $table = 'tipos_articulos_bolsa';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
