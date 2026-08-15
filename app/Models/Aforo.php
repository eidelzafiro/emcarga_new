<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Aforo extends Model
{
    protected $fillable = [
        'id_carta_porte',
        'id_factura',
        'id_prefactura',
        'fecha_parte',
        'flete_mt',
        'flete_mlc',
        'flete_demora',
        'otros_mt',
        'ingreso_mt',
        'descuento',
        'refactura',
        'id_user',

        // Tarifas por línea (1-5)
        'tarifa_mt_1', 'tarifa_mt_2', 'tarifa_mt_3', 'tarifa_mt_4', 'tarifa_mt_5',
        'flete_mt_1', 'flete_mt_2', 'flete_mt_3', 'flete_mt_4', 'flete_mt_5',
        'flete_mlc_1', 'flete_mlc_2', 'flete_mlc_3', 'flete_mlc_4', 'flete_mlc_5',
        'peso_cobrar_1', 'peso_cobrar_2', 'peso_cobrar_3', 'peso_cobrar_4', 'peso_cobrar_5',
        'desc_1', 'desc_2', 'desc_3', 'desc_4', 'desc_5', 'desc_6', 'desc_7', 'desc_8',

        // Almacenaje
        'almacenaje_peso', 'almacenaje_horas', 'almacenaje_tarifa', 'almacenaje_flete',

        // Demora
        'tar_dem_1', 'tar_dem_2', 'flete_dem_1', 'flete_dem_2',
        'dem_carga', 'dem_descarga', 'dem_total',
        'fecha_carga', 'hora_carga_1', 'hora_carga_2',
        'fecha_descarga', 'hora_descarga_1', 'hora_descarga_2',

        // Tiempos
        'tiempo_otros', 'tiempo_movimiento', 'tiempo_carga', 'tiempo_descarga',
        'tiempo_total', 'tiempo_feriado',

        // Recargos
        'recargo_1', 'recargo_2', 'recargo_3', 'recargo_4', 'recargo_5',

        // Salario / coeficiente
        'id_tasa', 'tasa', 'salario',

        // Indicadores (filas 1-2 + totales; 3-5 en `indicadores`)
        'viajes', 'tipo_indicadores',
        'id_tipo_carga_1', 'id_tipo_carga_2', 'id_tipo_carga_3', 'id_tipo_carga_4', 'id_tipo_carga_5',
        'distancia_1', 'distancia_2', 'distancia_3', 'distancia_4', 'distancia_5',
        'tn_pos_1', 'tn_pos_2', 'tn_pos_total',
        'tn_real_1', 'tn_real_2', 'tn_real_total',
        'km_carga_1', 'km_carga_2', 'km_carga_total',
        'km_vacio_1', 'km_vacio_2', 'km_vacio_total',
        'km_total_1', 'km_total_2', 'km_total_total',
        'traf_pos_1', 'traf_pos_2', 'traf_pos_total',
        'traf_real_1', 'traf_real_2', 'traf_real_total',
        'fecha_aforada',
    ];

    protected function casts(): array
    {
        return [
            'fecha_parte' => 'date',
            'fecha_carga' => 'date',
            'fecha_descarga' => 'date',
            'fecha_aforada' => 'datetime',
            'refactura' => 'boolean',
            'flete_mt' => 'decimal:2',
            'flete_mlc' => 'decimal:2',
            'flete_demora' => 'decimal:2',
            'otros_mt' => 'decimal:2',
            'ingreso_mt' => 'decimal:2',
            'descuento' => 'decimal:2',
            'tasa' => 'decimal:6',
            'salario' => 'decimal:2',
            'tipo_indicadores' => 'integer',
            'viajes' => 'integer',
        ];
    }

    public function cartaPorte(): BelongsTo
    {
        return $this->belongsTo(CartaPorte::class, 'id_carta_porte');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function prefactura(): BelongsTo
    {
        return $this->belongsTo(Prefactura::class, 'id_prefactura');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function tasa(): BelongsTo
    {
        return $this->belongsTo(Tasa::class, 'id_tasa');
    }

    public function indicadores(): HasOne
    {
        return $this->hasOne(Indicadore::class, 'id_carta_porte', 'id_carta_porte');
    }
}
