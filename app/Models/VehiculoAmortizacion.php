<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VehiculoAmortizacion extends Model
{
    protected $table = 'vehiculos_amortizacion';

    protected $fillable = [
        'vehiculo_type', 'vehiculo_id',
        'amortmn', 'amortme', 'vchapa',
    ];

    protected $casts = [
        'amortmn' => 'decimal:2',
        'amortme' => 'decimal:2',
        'vchapa' => 'decimal:2',
    ];

    public function vehiculo(): MorphTo
    {
        return $this->morphTo();
    }
}
