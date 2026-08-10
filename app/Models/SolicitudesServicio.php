<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudesServicio extends Model
{
    protected $table = 'solicitudes_servicio';

    protected $fillable = [
        'id_entidad',
        'numero',
        'id_cliente',
        'id_lugar_origen',
        'id_lugar_destino',
        'id_producto',
        'id_producto2',
        'id_tipo_carga',
        'id_tipo_carga2',
        'id_moneda',
        'id_user',
        'fecha_solicitud',
        'fecha_planificada',
        'fecha_ejecutada',
        'valor_mt',
        'valor_total',
        'peso1',
        'peso2',
        'distancia',
        'notas',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'date',
            'fecha_planificada' => 'date',
            'fecha_ejecutada' => 'date',
            'valor_mt' => 'decimal:2',
            'valor_total' => 'decimal:2',
            'peso1' => 'decimal:2',
            'peso2' => 'decimal:2',
            'distancia' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function lugarOrigen(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_lugar_origen');
    }

    public function lugarDestino(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_lugar_destino');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function producto2(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto2');
    }

    public function tipoCarga(): BelongsTo
    {
        return $this->belongsTo(TipoCarga::class, 'id_tipo_carga');
    }

    public function tipoCarga2(): BelongsTo
    {
        return $this->belongsTo(TipoCarga::class, 'id_tipo_carga2');
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class, 'id_moneda');
    }

    /**
     * Cartas de porte que amparan esta solicitud.
     * Las toneladas se acumulan desde ingreso_mt para seguir el cumplimiento.
     */
    public function cartasPorte(): HasMany
    {
        return $this->hasMany(CartaPorte::class, 'id_solicitud');
    }
}