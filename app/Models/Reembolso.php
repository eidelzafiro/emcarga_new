<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reembolso extends Model
{
    use SoftDeletes;

    protected $table = 'reembolsos';

    protected $fillable = [
        'id_bolsa',
        'fecha',
        'monto',
        'concepto',
        'documentos',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }
}
