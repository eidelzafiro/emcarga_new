<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoTasa extends Model
{
    protected $table = 'tipos_tasas';

    protected $fillable = [
        'codigo',
        'nombre',
        'unidad',
        'valor',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'valor' => 'decimal:4',
        ];
    }
}
