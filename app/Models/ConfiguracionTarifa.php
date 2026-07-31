<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionTarifa extends Model
{
    protected $table = 'configuraciones_tarifa';

    protected $fillable = [
        'demora_1', 'demora_2', 'kms_vacio_1', 'kms_vacio_2',
        'tarifa_horaria_1', 'tarifa_horaria_2',
        'kms_adicionales_1', 'kms_adicionales_2',
        'almacenaje', 'recargo_1', 'recargo_2', 'recargo_3_1',
        'recargo_3_2', 'recargo_3_3', 'recargo_4', 'recargo_5',
        'hora_1', 'hora_2', 'hora_3',
        'izaje_1', 'izaje_2', 'valor_izaje_mt', 'valor_izaje_me',
        'valor_almacenaje', 'plazo_libre_exp',
    ];

    protected function casts(): array
    {
        return [
            'demora_1' => 'decimal:2',
            'demora_2' => 'decimal:2',
            'kms_vacio_1' => 'decimal:2',
            'kms_vacio_2' => 'decimal:2',
            'tarifa_horaria_1' => 'decimal:2',
            'tarifa_horaria_2' => 'decimal:2',
            'kms_adicionales_1' => 'decimal:2',
            'kms_adicionales_2' => 'decimal:2',
            'almacenaje' => 'decimal:2',
            'recargo_1' => 'decimal:2',
            'recargo_2' => 'decimal:2',
            'recargo_3_1' => 'decimal:2',
            'recargo_3_2' => 'decimal:2',
            'recargo_3_3' => 'decimal:2',
            'recargo_4' => 'decimal:2',
            'recargo_5' => 'decimal:2',
            'izaje_1' => 'decimal:2',
            'izaje_2' => 'decimal:2',
            'valor_izaje_mt' => 'decimal:2',
            'valor_izaje_me' => 'decimal:2',
            'valor_almacenaje' => 'decimal:2',
        ];
    }
}
