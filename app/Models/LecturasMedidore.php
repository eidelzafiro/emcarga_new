<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturasMedidore extends Model
{
    protected $table = 'lecturas_medidores';

    protected $fillable = [
        'id_medidor', 'fecha_lectura', 'lectura_inicial',
        'lectura_final', 'consumo', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_lectura' => 'date',
            'lectura_inicial' => 'decimal:2',
            'lectura_final' => 'decimal:2',
            'consumo' => 'decimal:2',
        ];
    }

    public function medidor(): BelongsTo
    {
        return $this->belongsTo(Medidore::class, 'id_medidor');
    }
}
