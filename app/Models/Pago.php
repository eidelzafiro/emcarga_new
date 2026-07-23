<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id_tipo_documento',
        'id_moneda',
        'fecha_pago',
        'numero_documento',
        'monto',
        'concepto',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'id_tipo_documento');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'id_moneda');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
