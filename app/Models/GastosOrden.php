<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastosOrden extends Model
{
    protected $table = 'gastos_orden';

    protected $fillable = [
        'id_orden_taller', 'importe_me', 'vale', 'id_tipo_agregado',
        'nombre', 'cantidad', 'codigo_pieza', 'motivo', 'id_motor', 'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'importe_me' => 'decimal:2',
            'cantidad' => 'decimal:2',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenesTaller::class, 'id_orden_taller');
    }

    public function tipoAgregado(): BelongsTo
    {
        return $this->belongsTo(TipoAgregado::class, 'id_tipo_agregado');
    }

    public function motor(): BelongsTo
    {
        return $this->belongsTo(Motore::class, 'id_motor');
    }
}
