<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVale extends Model
{
    protected $table = 'detalles_vale';

    protected $fillable = [
        'id_vale',
        'id_inventario',
        'descripcion',
        'cantidad',
        'unidad',
        'precio_unitario',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function vale(): BelongsTo
    {
        return $this->belongsTo(Vale::class, 'id_vale');
    }

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'id_inventario');
    }
}
