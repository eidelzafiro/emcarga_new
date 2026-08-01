<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\TipoPagoAdicionale;

class TipoPenalizacione extends Model
{
    protected $table = 'tipos_penalizaciones';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
        'area_id',
        'tipo_pago_adicional_id',
        'porcentaje',
        'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'porcentaje' => 'decimal:2',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function tipoPagoAdicional(): BelongsTo
    {
        return $this->belongsTo(TipoPagoAdicionale::class, 'tipo_pago_adicional_id');
    }
}
