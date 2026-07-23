<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoTalla extends Model
{
    protected $table = 'tipos_tallas';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
