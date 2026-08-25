<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VehiculoPlan extends Model
{
    protected $table = 'vehiculos_planes';

    protected $fillable = [
        'vehiculo_type', 'vehiculo_id',
        'plan_comb', 'plan_tn', 'plan_viajes', 'plan_gastos', 'plan_cdt', 'plan_diario',
    ];

    protected $casts = [
        'plan_comb' => 'decimal:2',
        'plan_tn' => 'decimal:2',
        'plan_viajes' => 'decimal:2',
        'plan_gastos' => 'decimal:2',
        'plan_cdt' => 'decimal:2',
        'plan_diario' => 'decimal:2',
    ];

    public function vehiculo(): MorphTo
    {
        return $this->morphTo();
    }
}
