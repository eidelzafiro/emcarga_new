<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCargaCombustible extends Model
{
    protected $table = 'detalles_carga_combustible';

    protected $fillable = [
        'id_carga',
        'id_tarjeta',
        'fcarga',
        'folio',
        'saldo_mon',
        'saldo_lts',
    ];

    protected function casts(): array
    {
        return [
            'fcarga' => 'date',
            'saldo_mon' => 'decimal:2',
            'saldo_lts' => 'decimal:2',
        ];
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(CombustibleCarga::class, 'id_carga');
    }

    public function tarjeta(): BelongsTo
    {
        return $this->belongsTo(Tarjeta::class, 'id_tarjeta');
    }
}