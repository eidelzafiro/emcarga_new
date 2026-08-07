<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoTractivo extends Model
{
    protected $table = 'tipos_tractivos';

    protected $fillable = [
        'codigo', 'nombre', 'id_marca', 'id_modelo', 'id_pais',
        'id_tipo_mantenimiento',
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

    public function tipoCombustible(): BelongsTo
    {
        return $this->belongsTo(TipoCombustible::class, 'id_tipo_combustible');
    }

    public function tipoMantenimiento(): BelongsTo
    {
        return $this->belongsTo(TiposMantenimiento::class, 'id_tipo_mantenimiento');
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
}
