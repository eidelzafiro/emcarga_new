<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventario extends Model
{
    use SoftDeletes;

    protected $table = 'inventario';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria',
        'unidad_medida',
        'cantidad_actual',
        'costo_unitario',
        'costo_total',
        'ubicacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'cantidad_actual' => 'decimal:2',
            'costo_unitario' => 'decimal:2',
            'costo_total' => 'decimal:2',
        ];
    }
}
