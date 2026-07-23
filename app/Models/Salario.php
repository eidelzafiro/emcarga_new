<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salario extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'mes', 'ano', 'id_bolsa', 'id_movimiento', 'numero_nomina',
        'id_area', 'id_sexo', 'id_categoria_cargo', 'id_cargo',
        'id_tipo_sistema_pago', 'id_grupo_escala', 'id_nivel_educacion',
        'id_integracion_politica', 'id_color_piel',
        'salario_base', 'plus_base', 'tarifa', 'plus', 'cla',
        't_regular', 't_irregular', 't_garantia', 't_doblaje',
        't_nocturna_1', 't_nocturna_2', 't_feriados', 't_extra', 't_total',
        'imp_regular', 'imp_plus', 'imp_adicional', 'imp_cla',
        'imp_gps', 'imp_irregular', 'imp_nocturna_1', 'imp_nocturna_2',
        'imp_feriados', 'imp_maestrias', 'imp_g_electro', 'imp_garantia',
        'imp_doblaje', 'imp_h_extra', 'imp_reservas_alm', 'imp_otros',
        'imp_ir_resultado', 'pen_resultado', 'pen_importe', 'imp_resultado',
        'imp_salario_final', 'cpl', 'ri', 'cotizacion', 'salario_cotizacion',
        'observaciones', 'estado', 'id_user',
    ];

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'id_movimiento');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    public function tipoSistemaPago(): BelongsTo
    {
        return $this->belongsTo(TipoSistemaPago::class, 'id_tipo_sistema_pago');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
