<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Demanda extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fecha_demanda', 'id_cliente', 'id_producto', 'id_origen', 'id_destino',
        'id_embalaje', 'viajes', 'kms_totales', 'kms_carga', 'tiempo_demanda',
        'tiempo_aceptacion', 'datos_mensuales', 'observaciones', 'estado', 'id_user',
    ];

    protected function casts(): array
    {
        return [
            'datos_mensuales' => 'array',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function origen(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_origen');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_destino');
    }

    public function embalaje(): BelongsTo
    {
        return $this->belongsTo(Embalaje::class, 'id_embalaje');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
