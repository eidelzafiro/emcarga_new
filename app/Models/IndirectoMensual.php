<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndirectoMensual extends Model
{
    protected $table = 'indirectos_mensuales';

    protected $fillable = [
        'fcontabilidad',
        'dietas',
        'chapa',
        'combustiblemn',
        'lubricantemn',
        'piezasmn',
        'amortizacionmn',
        'salario',
        'vacaciones',
        'impuesto1',
        'impuesto2',
        'ogastosmn',
        'indirectotallermn',
        'indirectoadminmn',
        'ingresosmn',
        'toneladas',
        'trafico',
        'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'fcontabilidad' => 'date',
            'dietas' => 'decimal:2',
            'chapa' => 'decimal:2',
            'combustiblemn' => 'decimal:2',
            'lubricantemn' => 'decimal:2',
            'piezasmn' => 'decimal:2',
            'amortizacionmn' => 'decimal:2',
            'salario' => 'decimal:2',
            'vacaciones' => 'decimal:2',
            'impuesto1' => 'decimal:2',
            'impuesto2' => 'decimal:2',
            'ogastosmn' => 'decimal:2',
            'indirectotallermn' => 'decimal:2',
            'indirectoadminmn' => 'decimal:2',
            'ingresosmn' => 'decimal:2',
            'toneladas' => 'decimal:2',
            'trafico' => 'decimal:2',
        ];
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}