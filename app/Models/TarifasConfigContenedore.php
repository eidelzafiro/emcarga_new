<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifasConfigContenedore extends Model
{
    protected $table = 'tarifas_config_contenedor';

    protected $fillable = [
        'demora_1', 'demora_2',
        'kms_vacio_1', 'kms_vacio_2',
        'tarifa_horaria_1',
        'izaje_1', 'izaje_2',
        'valor_izaje_mt', 'valor_izaje_me',
        'valor_almacenaje',
        'plazo_libre_exp',
        'version',
    ];
}
