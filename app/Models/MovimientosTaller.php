<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientosTaller extends Model
{
    protected $table = 'movimientos_taller';

    protected $fillable = ['id_orden_taller', 'id_nave', 'id_valla', 'fecha_inicio', 'fecha_final', 'hora_inicio', 'hora_final', 'tiempo_minutos'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'datetime', 'fecha_final' => 'datetime'];
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
