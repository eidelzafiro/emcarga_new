<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionOrdenTaller extends Model
{
    protected $table = 'clasificaciones_ordenes_taller';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
