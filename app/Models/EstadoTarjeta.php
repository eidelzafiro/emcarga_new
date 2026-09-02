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
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'date',
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
