<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Giro extends Model
{
    protected $table = 'giros';

    protected $fillable = ['numero_carta_porte', 'id_solicitud', 'id_tractivo', 'id_cliente', 'id_lugar_origen', 'id_lugar_destino', 'id_producto', 'id_tipo_carga', 'id_moneda', 'id_user', 'fecha_parte', 'ingreso_mt', 'flete_mt', 'estado'];

    protected function casts(): array
    {
        return ['fecha_parte' => 'date', 'ingreso_mt' => 'decimal:2', 'flete_mt' => 'decimal:2'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }
}
