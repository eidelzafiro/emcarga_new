<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Indicadore extends Model
{
    protected $primaryKey = 'id_carta_porte';

    public $incrementing = false;

    protected $fillable = [
        'id_carta_porte',
        'tn_pos_3', 'tn_pos_4', 'tn_pos_5', 'tn_pos_6', 'tn_pos_7', 'tn_pos_8', 'tn_pos_9',
        'tn_pos_10', 'tn_pos_11', 'tn_pos_12', 'tn_pos_13', 'tn_pos_14', 'tn_pos_15',
        'km_pos_3', 'km_pos_4', 'km_pos_5', 'km_pos_6', 'km_pos_7', 'km_pos_8', 'km_pos_9',
        'km_pos_10', 'km_pos_11', 'km_pos_12', 'km_pos_13', 'km_pos_14',
        'traf_pos_3', 'traf_pos_4', 'traf_pos_5', 'traf_pos_6', 'traf_pos_7',
        'traf_pos_8', 'traf_pos_9', 'traf_pos_10', 'traf_pos_11', 'traf_pos_12',
    ];

    public function cartaPorte(): BelongsTo
    {
        return $this->belongsTo(CartaPorte::class, 'id_carta_porte');
    }
}
