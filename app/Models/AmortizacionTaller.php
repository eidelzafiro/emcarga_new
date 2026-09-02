<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AmortizacionTaller extends Model
{
    use HasFactory;

    protected $table = 'amortizacion_taller';

    protected $fillable = [
        'id_tractivo',
        'amortizacion_mn',
        'chapa',
        'id_entidad',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'amortizacion_mn' => 'decimal:2',
            'chapa' => 'decimal:2',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
