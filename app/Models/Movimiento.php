<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $table = 'movimientos';

    protected $fillable = [
        'id_bolsa', 'tipo_movimiento', 'fecha_movimiento',
        'id_entidad_origen', 'id_entidad_destino',
        'id_cargo', 'id_turno', 'salario', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'date',
            'salario' => 'decimal:2',
        ];
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    public function entidadOrigen(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad_origen');
    }

    public function entidadDestino(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad_destino');
    }
}
