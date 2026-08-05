<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'plan_comb', 'plan_tn', 'plan_viajes', 'plan_gastos', 'plan_cdt', 'plan_diario',
        'ficav', 'femision_ficav', 'fvence_ficav',
        'lot', 'femision_lot', 'fvence_lot',
        'circulacion', 'femision_circ', 'fvence_circ',
        'f_reconstruccion', 'gps',
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
        'plan_comb' => 'decimal:2',
        'plan_tn' => 'decimal:2',
        'plan_viajes' => 'decimal:2',
        'plan_gastos' => 'decimal:2',
        'plan_cdt' => 'decimal:2',
        'plan_diario' => 'decimal:2',
        'femision_ficav' => 'date',
        'fvence_ficav' => 'date',
        'femision_lot' => 'date',
        'fvence_lot' => 'date',
        'femision_circ' => 'date',
        'fvence_circ' => 'date',
        'f_reconstruccion' => 'date',
        'fecha_alta' => 'date',
        'fecha_baja' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoTractivo::class, 'id_tipo_vehiculo');
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
        return $this->belongsTo(Grupo::class, 'id_grupo');
    }

    public function tipoServicio(): BelongsTo
    {
        return $this->belongsTo(TipoServicio::class, 'id_tipo_servicio');
    }

    public function colorPrimario(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'id_color_primario');
    }

    public function colorSecundario(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'id_color_secundario');
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
