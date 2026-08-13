<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifaAcuerdo extends Model
{
    protected $table = 'tarifas_acuerdos';

    protected $fillable = [
        'id_cliente',
        'id_origen',
        'id_destino',
        'id_producto',
        'tarifa_mt',
        'flete_mt',
        'id_entidad',
        'origen_id',
    ];

    protected function casts(): array
    {
        return [
            'tarifa_mt' => 'decimal:2',
            'flete_mt' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function origen(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_origen');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_destino');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}
