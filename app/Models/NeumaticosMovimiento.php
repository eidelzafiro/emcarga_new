<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NeumaticosMovimiento extends Model
{
    protected $table = 'neumaticos_movimientos';

    protected $fillable = [
        'id_neumatico', 'id_tractivo', 'fecha_montaje', 'fecha_retiro',
        'km_instalado', 'km_retirado', 'posicion', 'id_destino', 'observaciones',
        'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'fecha_montaje' => 'date',
            'fecha_retiro' => 'date',
            'km_instalado' => 'decimal:2',
            'km_retirado' => 'decimal:2',
        ];
    }

    public function neumatico(): BelongsTo
    {
        return $this->belongsTo(Neumatico::class, 'id_neumatico');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }
}
