<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotoresMovimiento extends Model
{
    protected $table = 'motores_movimientos';

    protected $fillable = ['id_motor', 'id_tractivo', 'fecha_movimiento', 'tipo', 'observaciones'];

    protected function casts(): array
    {
        return ['fecha_movimiento' => 'date'];
    }

    public function motor(): BelongsTo
    {
        return $this->belongsTo(Motore::class, 'id_motor');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }
}
