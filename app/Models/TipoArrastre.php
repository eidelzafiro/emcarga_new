<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoArrastre extends Model
{
    protected $table = 'tipos_arrastres';

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'capacidad_toneladas',
        'id_marca', 'id_modelo', 'id_pais', 'id_tipo_equipo',
        'fabricacion', 'frecuencia',
        'id_medida_del', 'id_medida_tra', 'id_medida_res',
        'neum_del_cant', 'neum_tras_cant', 'neum_resp_cant',
        'id_tipo_suspension', 'ejes_cant', 'eject_trac',
        'dist_frente', 'dist_trasera', 'largo_garganta', 'altura_piso',
        'altura_total', 'largo_total', 'ancho_total',
        'id_tipo_combustible', 'id_lubricante', 'id_lub_cubo',
        'id_tipo_mantenimiento', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'capacidad_toneladas' => 'decimal:2',
            'fabricacion' => 'integer',
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

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'id_marca');
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class, 'id_modelo');
    }

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'id_pais');
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class, 'id_tipo_equipo');
    }

    public function medidaDel(): BelongsTo
    {
        return $this->belongsTo(MedidaNeumatico::class, 'id_medida_del');
    }

    public function medidaTra(): BelongsTo
    {
        return $this->belongsTo(MedidaNeumatico::class, 'id_medida_tra');
    }

    public function medidaRes(): BelongsTo
    {
        return $this->belongsTo(MedidaNeumatico::class, 'id_medida_res');
    }

    public function tipoSuspension(): BelongsTo
    {
        return $this->belongsTo(TipoSuspension::class, 'id_tipo_suspension');
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'id_tipo_combustible');
    }

    public function lubricante(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lubricante');
    }

    public function lubCubo(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lub_cubo');
    }

    public function tipoMantenimiento(): BelongsTo
    {
        return $this->belongsTo(TiposMantenimiento::class, 'id_tipo_mantenimiento');
    }
}
