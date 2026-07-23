<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pieza extends Model
{
    use SoftDeletes;

    protected $table = 'piezas';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'unidad_medida', 'costo_unitario', 'stock_minimo', 'stock_actual', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'costo_unitario' => 'decimal:2'];
    }
}
