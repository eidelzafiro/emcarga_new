<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstadisticasExplotacion extends Model
{
    protected $table = 'estadisticas_explotacion';

    protected $fillable = ['id_hoja_ruta', 'fecha_indicadores', 'viajes', 'kms_carga', 'kms_vacio', 'kms_total', 'toneladas_posibles', 'toneladas_reales', 'trafico_posible', 'trafico_producido'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function hojaRuta(): BelongsTo
    {
        return $this->belongsTo(HojasRuta::class, 'id_hoja_ruta');
    }
}
