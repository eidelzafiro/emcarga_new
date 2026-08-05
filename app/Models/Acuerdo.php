<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Acuerdo extends Model
{
    use SoftDeletes;

    protected $table = 'acuerdos';

    protected $fillable = [
        'id_cliente',
        'id_lugar_origen',
        'id_lugar_destino',
        'id_producto',
        'tarifa_ton',
        'importe',
        'id_entidad',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'tarifa_ton' => 'decimal:2',
            'importe' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function origen(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_lugar_origen');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_lugar_destino');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}