<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AforoLinea extends Model
{
    protected $fillable = [
        'id_aforo',
        'posicion',
        'id_tipo_carga',
        'distancia',
        'peso_cobrar',
        'descuento',
        'tarifa_mt',
        'flete_mt',
        'flete_mlc',
    ];

    protected function casts(): array
    {
        return [
            'posicion' => 'integer',
            'distancia' => 'integer',
            'peso_cobrar' => 'decimal:3',
            'descuento' => 'decimal:2',
            'tarifa_mt' => 'decimal:2',
            'flete_mt' => 'decimal:2',
            'flete_mlc' => 'decimal:2',
        ];
    }

    public function aforo(): BelongsTo
    {
        return $this->belongsTo(Aforo::class, 'id_aforo');
    }

    public function tipoCarga(): BelongsTo
    {
        return $this->belongsTo(TipoCarga::class, 'id_tipo_carga');
    }
}
