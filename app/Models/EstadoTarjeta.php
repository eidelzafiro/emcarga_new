<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstadoTarjeta extends Model
{
    protected $table = 'estados_tarjetas';

    protected $fillable = [
        'id_tarjeta',
        'fecha_movimiento',
        'id_entrega',
        'id_recibe',
        'saldo_mn',
        'saldo_mlc',
        'comprobante',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'date',
            'saldo_mn' => 'decimal:2',
            'saldo_mlc' => 'decimal:2',
        ];
    }

    public function tarjeta(): BelongsTo
    {
        return $this->belongsTo(Tarjeta::class, 'id_tarjeta');
    }

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_entrega');
    }

    public function recibe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_recibe');
    }
}
