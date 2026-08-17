<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenesOperacione extends Model
{
    protected $table = 'ordenes_operaciones';

    protected $fillable = [
        'id_orden_taller', 'id_tipo_operacion',
        'id_operario', 'id_operario2', 'id_operario3',
        'fecha_inicio', 'hora_inicio', 'fecha_final', 'hora_final',
        'tiempo', 'id_nave', 'id_valla', 'id_entidad',
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

    public function tipoOperacion(): BelongsTo
    {
        return $this->belongsTo(TiposOperacione::class, 'id_tipo_operacion');
    }

    public function operario(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_operario');
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
