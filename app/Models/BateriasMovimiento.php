<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BateriasMovimiento extends Model
{
    protected $table = 'baterias_movimientos';

    protected $fillable = [
        'id_bateria', 'id_tractivo', 'fecha_movimiento', 'tipo',
        'fecha_retiro', 'tiempo_trabajo', 'observaciones', 'id_destino',
        'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'date',
            'fecha_retiro' => 'date',
        ];
    }

    public function bateria(): BelongsTo
    {
        return $this->belongsTo(Bateria::class, 'id_bateria');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(DestinoAgregado::class, 'id_destino');
    }
}
