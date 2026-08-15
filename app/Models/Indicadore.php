<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Indicadore extends Model
{
    protected $table = 'indicadores';

    protected $primaryKey = 'id_carta_porte';

    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = [
        'id_carta_porte',
        // Líneas 3-7 (paridad com_indicadores legacy; las 1-2 y totales viven en `aforos`)
        'tn_pos_3', 'tn_real_3', 'km_carga_3', 'km_vacio_3', 'kms_total_3', 'traf_real_3', 'traf_pos_3',
        'tn_pos_4', 'tn_real_4', 'km_carga_4', 'km_vacio_4', 'kms_total_4', 'traf_real_4', 'traf_pos_4',
        'tn_pos_5', 'tn_real_5', 'km_carga_5', 'km_vacio_5', 'kms_total_5', 'traf_real_5', 'traf_pos_5',
        'tn_pos_6', 'tn_real_6', 'km_carga_6', 'km_vacio_6', 'kms_total_6', 'traf_real_6', 'traf_pos_6',
        'tn_pos_7', 'tn_real_7', 'km_carga_7', 'km_vacio_7', 'kms_total_7', 'traf_real_7', 'traf_pos_7',
    ];

    protected function casts(): array
    {
        $campos = [];
        foreach (range(3, 7) as $n) {
            foreach (['tn_pos_', 'tn_real_', 'km_carga_', 'km_vacio_', 'kms_total_', 'traf_real_', 'traf_pos_'] as $p) {
                $campos[$p.$n] = 'decimal:2';
            }
        }

        return $campos;
    }

    public function cartaPorte(): BelongsTo
    {
        return $this->belongsTo(CartaPorte::class, 'id_carta_porte');
    }
}
