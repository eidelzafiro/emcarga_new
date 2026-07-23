<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuncionesCargo extends Model
{
    protected $table = 'funciones_cargo';

    protected $fillable = ['id_cargo', 'funcion', 'descripcion', 'orden'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
