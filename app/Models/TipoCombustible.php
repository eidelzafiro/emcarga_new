<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCombustible extends Model
{
    protected $table = 'tipos_combustibles';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
        'preciomn',
        'elementomn',
        'factor',
        'existfincmn',
        'indice',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'preciomn' => 'decimal:2',
            'elementomn' => 'decimal:2',
            'factor' => 'decimal:2',
            'existfincmn' => 'decimal:2',
            'indice' => 'decimal:2',
        ];
    }
}
