<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiposSubctaUnidad extends Model
{
    protected $table = 'tipos_subcta_unidad';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
