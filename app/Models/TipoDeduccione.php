<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDeduccione extends Model
{
    protected $table = 'tipos_deducciones';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'clave',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
