<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIngreso extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'siglas',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
