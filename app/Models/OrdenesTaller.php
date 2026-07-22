<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenesTaller extends Model
{
    use SoftDeletes;

    protected $table = 'ordenes_taller';

    protected $fillable = [
        'numero', 'id_tractivo', 'id_tipo_mantenimiento',
        'fecha_ingreso', 'fecha_salida_estimada', 'fecha_salida_real',
        'kilometraje', 'estado', 'diagnostico', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'fecha_salida_estimada' => 'date',
            'fecha_salida_real' => 'date',
            'kilometraje' => 'decimal:2',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function tipoMantenimiento(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TiposMantenimiento::class, 'id_tipo_mantenimiento');
    }
}
