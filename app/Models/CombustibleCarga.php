<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CombustibleCarga extends Model
{
    use SoftDeletes;

    protected $table = 'combustible_cargas';

    protected $fillable = [
        'numero',
        'id_tarjeta',
        'id_tractivo',
        'id_bolsa',
        'fecha_carga',
        'cantidad_litros',
        'precio_litro',
        'total',
        'tipo_combustible',
        'lugar',
        'observaciones',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha_carga' => 'date',
            'cantidad_litros' => 'decimal:2',
            'precio_litro' => 'decimal:4',
            'total' => 'decimal:2',
        ];
    }

    public function tarjeta(): BelongsTo
    {
        return $this->belongsTo(Tarjeta::class, 'id_tarjeta');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
