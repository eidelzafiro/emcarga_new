<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CombustibleCarga extends Model
{
    use SoftDeletes;

    protected $table = 'combustible_cargas';

    protected $fillable = [
        'fcarga',
        'saldocargado',
        'saldoxtarjeta',
        'id_monedas',
        'id_tipo_combustibles',
        'id_responsable',
        'folio',
        'notas',
        'id_entidad',
        'estado',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'fcarga' => 'date',
            'saldocargado' => 'decimal:2',
            'saldoxtarjeta' => 'decimal:2',
        ];
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'id_monedas');
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'id_tipo_combustibles');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_responsable');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCargaCombustible::class, 'id_carga');
    }
}