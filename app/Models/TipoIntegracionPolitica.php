<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIntegracionPolitica extends Model
{
    protected $fillable = [
        'codigo',
        'nombre',
        'politica',
        'abreviatura',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
