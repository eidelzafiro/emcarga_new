<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarifa extends Model
{
    protected $fillable = ['id_tipo_carga', 'kms', 'tarifa_mt', 'version'];

    public function tipoCarga(): BelongsTo
    {
        return $this->belongsTo(TipoCarga::class, 'id_tipo_carga');
    }
}
