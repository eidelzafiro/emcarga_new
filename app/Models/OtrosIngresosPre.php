<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtrosIngresosPre extends Model
{
    protected $table = 'otros_ingresos_pre';

    protected $fillable = ['id_carta_porte', 'id_tipo_ingreso', 'cantidad', 'importe_mn'];

    public function cartaPorte(): BelongsTo
    {
        return $this->belongsTo(CartaPorte::class, 'id_carta_porte');
    }

    public function tipoIngreso(): BelongsTo
    {
        return $this->belongsTo(TipoIngreso::class, 'id_tipo_ingreso');
    }
}
