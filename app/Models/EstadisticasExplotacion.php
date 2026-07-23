<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadisticasExplotacion extends Model
{
    protected $table = 'estadisticas_explotacion';

    protected $fillable = ['id_hoja_ruta', 'fecha_indicadores', 'viajes', 'kms_carga', 'kms_vacio', 'kms_total', 'toneladas_posibles', 'toneladas_reales', 'trafico_posible', 'trafico_producido'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
