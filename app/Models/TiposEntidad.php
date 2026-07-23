<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiposEntidad extends Model
{
    protected $table = 'tipos_entidad';

    protected $fillable = ['id_organismo', 'codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
