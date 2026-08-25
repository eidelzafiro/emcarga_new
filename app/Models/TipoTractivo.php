<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class TipoTractivo extends Model
{
    protected $table = 'tipos_tractivos';

    protected $fillable = [
        'fabricacion', 'tipo_equipo',
        'bat_cant', 'bat_amp',
        'dif_cant', 'dif_relacion', 'dif_ancho',
        'id_medida_del', 'id_medida_tra', 'id_medida_res',
        'neum_del_cant', 'neum_tras_cant', 'neum_resp_cant', 'neum_tractivos',
        'ejes_cant', 'eject_trac',
        'id_tipo_combustible', 'id_lubricante_motor', 'id_lubricante_cubo',
        'lub_norma', 'lub_caja',
        'dist_eje_inter', 'dist_eje_tras',
        'cama_largo', 'cama_ancho', 'cama_altura', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function getCantidadVehiculosAttribute(): int
    {
        if (array_key_exists('tractivos_count', $this->attributes)) {
            return (int) $this->attributes['tractivos_count'];
        }

        $ids = TipoVehiculo::where('id_tipo_tractivo', $this->id)->pluck('id');

        return (int) DB::table('tractivos')->whereIn('id_tipo_vehiculo', $ids)->count();
    }

    /**
     * Tipo de vehículo unificado (tabla tipo_vehiculos) que referencia a
     * este tipo de tractivo. La marca/modelo/tipo_equipo/tipo_mantenimiento
     * viven allí desde la Fase B.
     */
    public function tipoVehiculo(): HasOne
    {
        return $this->hasOne(TipoVehiculo::class, 'id_tipo_tractivo');
    }

    /**
     * Vehículos (tractivos) que usan este tipo, a través de tipo_vehiculos.
     */
    public function tractivos(): HasManyThrough
    {
        return $this->hasManyThrough(
            Tractivo::class,
            TipoVehiculo::class,
            'id_tipo_tractivo',
            'id_tipo_vehiculo',
            'id',
            'id'
        );
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'id_tipo_combustible');
    }

    public function lubricanteMotor(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lubricante_motor');
    }

    public function lubricanteCubo(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lubricante_cubo');
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
}
