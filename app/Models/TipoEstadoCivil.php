<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEstadoCivil extends Model
{
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
