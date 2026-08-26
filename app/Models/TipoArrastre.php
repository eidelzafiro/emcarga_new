<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class TipoArrastre extends Model
{
    protected $table = 'tipos_arrastres';

    protected $fillable = [
        'frecuencia',
        'id_medida_del', 'id_medida_tra', 'id_medida_res',
        'neum_del_cant', 'neum_tras_cant', 'neum_resp_cant',
        'id_tipo_suspension', 'ejes_cant', 'eject_trac',
        'dist_frente', 'dist_trasera', 'largo_garganta', 'altura_piso',
        'altura_total', 'largo_total', 'ancho_total',
        'id_lubricante', 'id_lub_cubo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'frecuencia' => 'integer',
            'neum_del_cant' => 'integer',
            'neum_tras_cant' => 'integer',
            'neum_resp_cant' => 'integer',
            'ejes_cant' => 'integer',
            'dist_frente' => 'decimal:2',
            'dist_trasera' => 'decimal:2',
            'largo_garganta' => 'decimal:2',
            'altura_piso' => 'decimal:2',
            'altura_total' => 'decimal:2',
            'largo_total' => 'decimal:2',
            'ancho_total' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function getCantidadVehiculosAttribute(): int
    {
        if (array_key_exists('tractivos_count', $this->attributes)) {
            return (int) $this->attributes['tractivos_count'];
        }

        $ids = TipoVehiculo::where('id_tipo_arrastre', $this->id)->pluck('id');

        return (int) DB::table('tractivos')->whereIn('id_tipo_vehiculo', $ids)->count();
    }

    /**
     * Tipo de vehículo unificado (tabla tipo_vehiculos) que referencia a
     * este tipo de arrastre. La marca/modelo/tipo_equipo/tipo_mantenimiento
     * viven allí desde la Fase B.
     */
    public function tipoVehiculo(): HasOne
    {
        return $this->hasOne(TipoVehiculo::class, 'id_tipo_arrastre');
    }

    /**
     * Vehículos (arrastres) que usan este tipo, a través de tipo_vehiculos.
     */
    public function tractivos(): HasManyThrough
    {
        return $this->hasManyThrough(
            Tractivo::class,
            TipoVehiculo::class,
            'id_tipo_arrastre',
            'id_tipo_vehiculo',
            'id',
            'id'
        );
    }

    public function medidaDel(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_medida_del');
    }

    public function medidaTra(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_medida_tra');
    }

    public function medidaRes(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_medida_res');
    }

    public function tipoSuspension(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_tipo_suspension');
    }

    public function lubricante(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lubricante');
    }

    public function lubCubo(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lub_cubo');
    }
}
