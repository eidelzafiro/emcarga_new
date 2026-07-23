<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartaPorte extends Model
{
    use SoftDeletes;

    protected $table = 'cartas_porte';

    protected $fillable = [
        'numero',
        'id_hoja_ruta',
        'id_cliente',
        'id_lugar_origen',
        'id_lugar_destino',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function lugarOrigen(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_lugar_origen');
    }

    public function lugarDestino(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_lugar_destino');
    }
}
