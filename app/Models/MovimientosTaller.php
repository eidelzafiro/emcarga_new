<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientosTaller extends Model
{
    protected $table = 'movimientos_taller';

    protected $fillable = [
        'id_orden_taller', 'id_nave', 'id_valla',
        'fecha_inicio', 'hora_inicio', 'fecha_final', 'hora_final',
        'tiempo', 'observaciones', 'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_final' => 'date',
            'tiempo' => 'decimal:2',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenesTaller::class, 'id_orden_taller');
    }

    public function nave(): BelongsTo
    {
        return $this->belongsTo(Nave::class, 'id_nave');
    }

    public function valla(): BelongsTo
    {
        return $this->belongsTo(Valla::class, 'id_valla');
    }
}
