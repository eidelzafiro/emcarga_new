<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pizarra extends Model
{
    protected $table = 'pizarra';

    protected $fillable = [
        'tractivo_id',
        'conductor_id',
        'estado',
        'ubicacion',
        'origen',
        'destino',
        'salida',
        'llegada_estimada',
        'llegada_real',
        'carga',
        'tonelaje',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'salida' => 'datetime',
            'llegada_estimada' => 'datetime',
            'llegada_real' => 'datetime',
            'tonelaje' => 'decimal:2',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class);
    }

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conductor_id');
    }
}
