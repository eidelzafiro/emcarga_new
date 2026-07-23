<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPenalizacione extends Model
{
    protected $table = 'tipos_penalizaciones';

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
