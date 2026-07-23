<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtrosGasto extends Model
{
    use SoftDeletes;

    protected $table = 'otros_gastos';

    protected $fillable = [
        'id_bolsa',
        'id_tractivo',
        'id_tipo_concepto',
        'fecha',
        'concepto',
        'monto_mn',
        'monto_mlc',
        'descripcion',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto_mn' => 'decimal:2',
            'monto_mlc' => 'decimal:2',
        ];
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function tipoConcepto(): BelongsTo
    {
        return $this->belongsTo(TipoConcepto::class, 'id_tipo_concepto');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
