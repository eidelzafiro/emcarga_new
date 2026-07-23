<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifasConfigCarga extends Model
{
    protected $table = 'tarifas_config_carga';

    protected $fillable = [
        'demora_1', 'demora_2',
        'kms_vacio_1', 'kms_vacio_2',
        'tarifa_horaria_1', 'tarifa_horaria_2',
        'kms_adicionales_1', 'kms_adicionales_2',
        'almacenaje',
        'recargo_1', 'recargo_2', 'recargo_3_1', 'recargo_3_2', 'recargo_3_3',
        'recargo_4', 'recargo_5',
        'hora_1', 'hora_2', 'hora_3',
        'version',
    ];
}
