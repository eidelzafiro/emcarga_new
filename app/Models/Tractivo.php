<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\CatalogoItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

class Tractivo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'tractivos';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id_entidad', 'codigo', 'descripcion', 'placa', 'id_tipo_vehiculo',
        'id_tipo_equipo', 'id_tipo_combustible',
        'id_motor', 'id_caja', 'id_diferencial',
        'id_grupo', 'id_tipo_servicio', 'id_color_primario', 'id_color_secundario',
        'id_tipo_estado', 'id_lubricante_hidraulico',
        'marca', 'modelo', 'anno', 'color',
        'numero_motor', 'numero_chasis', 'numero_caja',
        'capacidad_toneladas', 'capacidad_m3',
        'vin', 'nro_carroceria', 'nro_registro', 'nro_resolucion',
        'tara', 'cap_deposito', 'cap_hidraulico', 'cta_combustible',
        'indice_consumo', 'indice_aceite',
        'estado', 'fecha_alta', 'fecha_baja', 'kilometraje_actual',
        'kms_disp', 'kms_plan_mtto',
        'gps',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'anno' => 'integer',
        'tara' => 'decimal:2',
        'cap_deposito' => 'decimal:2',
        'cap_hidraulico' => 'decimal:2',
        'indice_consumo' => 'decimal:2',
        'indice_aceite' => 'decimal:2',
        'kms_disp' => 'decimal:2',
        'kms_plan_mtto' => 'integer',
        'fecha_alta' => 'date',
        'fecha_baja' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Fichas extraídas a tablas polimórficas (Fase C). Se exponen como
     * atributos virtuales para no romper el código existente (formularios,
     * CostoCalculoService, etc.).
     */
    public function amortizacion(): MorphOne
    {
        return $this->morphOne(VehiculoAmortizacion::class, 'vehiculo');
    }

    public function planes(): MorphOne
    {
        return $this->morphOne(VehiculoPlan::class, 'vehiculo');
    }

    public function documentacion(): MorphOne
    {
        return $this->morphOne(VehiculoDocumentacion::class, 'vehiculo');
    }

    public function getAmortmnAttribute()
    {
        return $this->amortizacion?->amortmn;
    }

    public function getAmortmeAttribute()
    {
        return $this->amortizacion?->amortme;
    }

    public function getVchapaAttribute()
    {
        return $this->amortizacion?->vchapa;
    }

    public function getPlanCombAttribute()
    {
        return $this->planes?->plan_comb;
    }

    public function getPlanTnAttribute()
    {
        return $this->planes?->plan_tn;
    }

    public function getPlanViajesAttribute()
    {
        return $this->planes?->plan_viajes;
    }

    public function getPlanGastosAttribute()
    {
        return $this->planes?->plan_gastos;
    }

    public function getPlanCdtAttribute()
    {
        return $this->planes?->plan_cdt;
    }

    public function getPlanDiarioAttribute()
    {
        return $this->planes?->plan_diario;
    }

    public function getFicavAttribute()
    {
        return $this->documentacion?->ficav;
    }

    public function getFemisionFicavAttribute()
    {
        return $this->documentacion?->femision_ficav;
    }

    public function getFvenceFicavAttribute()
    {
        return $this->documentacion?->fvence_ficav;
    }

    public function getLotAttribute()
    {
        return $this->documentacion?->lot;
    }

    public function getFemisionLotAttribute()
    {
        return $this->documentacion?->femision_lot;
    }

    public function getFvenceLotAttribute()
    {
        return $this->documentacion?->fvence_lot;
    }

    public function getCirculacionAttribute()
    {
        return $this->documentacion?->circulacion;
    }

    public function getFemisionCircAttribute()
    {
        return $this->documentacion?->femision_circ;
    }

    public function getFvenceCircAttribute()
    {
        return $this->documentacion?->fvence_circ;
    }

    public function getFReconstruccionAttribute()
    {
        return $this->documentacion?->f_reconstruccion;
    }

    /**
     * Persiste las fichas polimórficas desde los campos del formulario.
     */
    public function syncVehiculoExtra(array $datos): void
    {
        $amort = array_filter([
            'amortmn' => $datos['amortmn'] ?? null,
            'amortme' => $datos['amortme'] ?? null,
            'vchapa' => $datos['vchapa'] ?? null,
        ], fn ($v) => $v !== null);

        $plan = array_filter([
            'plan_comb' => $datos['plan_comb'] ?? null,
            'plan_tn' => $datos['plan_tn'] ?? null,
            'plan_viajes' => $datos['plan_viajes'] ?? null,
            'plan_gastos' => $datos['plan_gastos'] ?? null,
            'plan_cdt' => $datos['plan_cdt'] ?? null,
            'plan_diario' => $datos['plan_diario'] ?? null,
        ], fn ($v) => $v !== null);

        $doc = array_filter([
            'ficav' => $datos['ficav'] ?? null,
            'femision_ficav' => $datos['femision_ficav'] ?? null,
            'fvence_ficav' => $datos['fvence_ficav'] ?? null,
            'lot' => $datos['lot'] ?? null,
            'femision_lot' => $datos['femision_lot'] ?? null,
            'fvence_lot' => $datos['fvence_lot'] ?? null,
            'circulacion' => $datos['circulacion'] ?? null,
            'femision_circ' => $datos['femision_circ'] ?? null,
            'fvence_circ' => $datos['fvence_circ'] ?? null,
            'f_reconstruccion' => $datos['f_reconstruccion'] ?? null,
        ], fn ($v) => $v !== null);

        DB::transaction(function () use ($amort, $plan, $doc) {
            if (! empty($amort)) {
                $this->amortizacion()->updateOrCreate([], $amort);
            } else {
                $this->amortizacion()?->delete();
            }
            if (! empty($plan)) {
                $this->planes()->updateOrCreate([], $plan);
            } else {
                $this->planes()?->delete();
            }
            if (! empty($doc)) {
                $this->documentacion()->updateOrCreate([], $doc);
            } else {
                $this->documentacion()?->delete();
            }
        });
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class, 'id_tipo_vehiculo');
    }

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class, 'id_tipo_equipo');
    }

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'id_tipo_combustible');
    }

    public function motor(): BelongsTo
    {
        return $this->belongsTo(Motore::class, 'id_motor');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'id_caja');
    }

    public function diferencial(): BelongsTo
    {
        return $this->belongsTo(Diferenciale::class, 'id_diferencial');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_grupo');
    }

    public function tipoServicio(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_tipo_servicio');
    }

    public function colorPrimario(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_color_primario');
    }

    public function colorSecundario(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_color_secundario');
    }

    public function tipoEstado(): BelongsTo
    {
        return $this->belongsTo(EstadoComponente::class, 'id_tipo_estado');
    }

    public function lubricanteHidraulico(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lubricante_hidraulico');
    }
}
