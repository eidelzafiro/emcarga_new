<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEquipo extends Model
{
    protected $table = 'tipos_equipos';

    protected $fillable = ['nombre', 'activo', 'imagen'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
