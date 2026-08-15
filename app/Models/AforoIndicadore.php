<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AforoIndicadore extends Model
{
    protected $fillable = [
        'id_aforo',
        'posicion',
        'tn_pos',
        'tn_real',
        'km_carga',
        'km_vacio',
        'km_total',
        'traf_pos',
        'traf_real',
    ];

    protected function casts(): array
    {
        return [
            'posicion' => 'integer',
            'tn_pos' => 'decimal:2',
            'tn_real' => 'decimal:2',
            'km_carga' => 'decimal:2',
            'km_vacio' => 'decimal:2',
            'km_total' => 'decimal:2',
            'traf_pos' => 'decimal:2',
            'traf_real' => 'decimal:2',
        ];
    }

    public function aforo(): BelongsTo
    {
        return $this->belongsTo(Aforo::class, 'id_aforo');
    }
}
