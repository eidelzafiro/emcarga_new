<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenesTaller extends Model
{
    use SoftDeletes;

    protected $table = 'ordenes_taller';

    protected $fillable = [
        'numero', 'id_tractivo', 'id_tipo_mantenimiento',
        'fecha_ingreso', 'hora_ingreso', 'fecha_salida', 'hora_salida', 'ottiempo',
        'id_user', 'id_motivo_entrada', 'id_clasificacion', 'cant_clasificacion',
        'id_reporte', 'id_confeccionado', 'id_operario', 'notas', 'cancelada',
        'tipo_mtto', 'km_mtto', 'planificacion', 'km_mtto_prox',
        'ot_paralizado', 'ot_rotura_en_linea', 'ot_largo_plazo', 'comb_taller',
        'id_motor', 'id_taller', 'id_unidad', 'id_entidad',
        'pl_cons_comb', 'pl_cons_aceite', 'pl_cil1', 'pl_cil2', 'pl_cil3', 'pl_cil4',
        'pl_cil5', 'pl_cil6', 'pl_cil7', 'pl_cil8',
        'pl_presion_aceite_baja', 'pl_presion_aceite_alta', 'pl_temp_agua',
        'pl_temp_aceite', 'pl_observacion',
        // Campos del esquema previo (ETL 59 filas)
        'fecha_salida_estimada', 'fecha_salida_real', 'kilometraje', 'estado', 'diagnostico', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'fecha_salida' => 'date',
            'fecha_salida_estimada' => 'date',
            'fecha_salida_real' => 'date',
            'ottiempo' => 'decimal:2',
            'kilometraje' => 'decimal:2',
            'cancelada' => 'boolean',
            'comb_taller' => 'decimal:2',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function tipoMantenimiento(): BelongsTo
    {
        return $this->belongsTo(TiposMantenimiento::class, 'id_tipo_mantenimiento');
    }

    public function motivoEntrada(): BelongsTo
    {
        return $this->belongsTo(MotivosEntradaTaller::class, 'id_motivo_entrada');
    }

    public function clasificacion(): BelongsTo
    {
        return $this->belongsTo(ClasificacionOrdenTaller::class, 'id_clasificacion');
    }

    public function motor(): BelongsTo
    {
        return $this->belongsTo(Motore::class, 'id_motor');
    }

    public function taller(): BelongsTo
    {
        return $this->belongsTo(Taller::class, 'id_taller');
    }

    public function operaciones(): HasMany
    {
        return $this->hasMany(OrdenesOperacione::class, 'id_orden_taller');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(GastosOrden::class, 'id_orden_taller');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientosTaller::class, 'id_orden_taller');
    }

    /**
     * Tiempo total de la OT = suma de tiempos de operaciones (o diferencia
     * entre apertura y cierre si no hay operaciones).
     */
    public function calcularTiempoTotal(): float
    {
        $ops = (float) $this->operaciones()->sum('tiempo');
        if ($ops > 0) {
            return round($ops, 2);
        }

        if (! $this->fecha_ingreso || ! $this->fecha_salida) {
            return $this->ottiempo ?? 0;
        }

        try {
            $inicio = \Carbon\Carbon::parse($this->fecha_ingreso.($this->hora_ingreso ? ' '.$this->hora_ingreso : ''));
            $final = \Carbon\Carbon::parse($this->fecha_salida.($this->hora_salida ? ' '.$this->hora_salida : ''));

            return round(max(0, $final->diffInMinutes($inicio) / 60), 2);
        } catch (\Throwable) {
            return $this->ottiempo ?? 0;
        }
    }
}
