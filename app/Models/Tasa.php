<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tasa extends Model
{
    protected $fillable = [
        'nombre',
        'tasa',
        'tasa2',
        'id_tipo_carga',
        'distancia_1',
        'distancia_2',
        'capacidad_1',
        'capacidad_2',
        'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'tasa' => 'decimal:6',
            'tasa2' => 'decimal:6',
            'distancia_1' => 'integer',
            'distancia_2' => 'integer',
            'capacidad_1' => 'integer',
            'capacidad_2' => 'integer',
        ];
    }

    public function tipoCarga(): BelongsTo
    {
        return $this->belongsTo(TipoCarga::class, 'id_tipo_carga');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
