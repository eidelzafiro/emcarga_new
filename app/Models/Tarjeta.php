<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarjeta extends Model
{
    use SoftDeletes;

    protected $table = 'tarjetas';

    protected $fillable = [
        'numero',
        'descripcion',
        'id_cliente',
        'saldo_actual',
        'fcompra',
        'fvence',
        'saldoinicialmon',
        'saldoiniciallts',
        'saldoactuallts',
        'saldotransferenciamon',
        'saldotransferencialts',
        'idmonedas',
        'idtipocombustibles',
        'idempleado',
        'idtractivos',
        'idchofer',
        'cancelado',
        'inactiva',
        'fmovimiento',
        'fcancelado',
        'fcierre',
        'id_entidad',
        'limite_credito',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'saldo_actual' => 'decimal:2',
            'limite_credito' => 'decimal:2',
            'fcompra' => 'date',
            'fvence' => 'date',
            'saldoinicialmon' => 'decimal:2',
            'saldoiniciallts' => 'decimal:2',
            'saldoactuallts' => 'decimal:2',
            'saldotransferenciamon' => 'decimal:2',
            'saldotransferencialts' => 'decimal:2',
            'fmovimiento' => 'date',
            'fcancelado' => 'date',
            'fcierre' => 'date',
            'cancelado' => 'boolean',
            'inactiva' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'idmonedas');
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'idtipocombustibles');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'idempleado');
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'idchofer');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'idtractivos');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function cierres(): HasMany
    {
        return $this->hasMany(CierreTarjeta::class, 'id_tarjeta');
    }

    public function descargas(): HasMany
    {
        return $this->hasMany(CombustibleDescarga::class, 'id_tarjeta');
    }
}