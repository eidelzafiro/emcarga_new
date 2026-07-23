<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoModelo extends Model
{
    protected $table = 'tipos_modelo';

    protected $fillable = ['codigo', 'id_tipo_modelo', 'modelo', 'ancho', 'alto', 'activo'];

    public function tipoModelo(): BelongsTo
    {
        return $this->belongsTo(TipoModelo::class, 'id_tipo_modelo');
    }
}
