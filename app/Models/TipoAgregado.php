<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoAgregado extends Model
{
    protected $table = 'tipos_agregados';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
