<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentrosCosto extends Model
{
    protected $table = 'centros_costos';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
