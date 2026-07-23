<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCargaCombustible extends Model
{
    protected $table = 'detalles_carga_combustible';

    protected $fillable = [
        'id_carga',
        'id_tractivo',
        'id_bolsa',
        'fecha_movimiento',
        'comprobante',
        'importe_mn',
        'importe_mlc',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'date',
            'importe_mn' => 'decimal:2',
            'importe_mlc' => 'decimal:2',
        ];
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(CombustibleCarga::class, 'id_carga');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }
}
