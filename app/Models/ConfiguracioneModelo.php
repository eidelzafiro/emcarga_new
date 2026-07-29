<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracioneModelo extends Model
{
    protected $table = 'configuraciones_modelo';

    protected $fillable = ['nombre', 'codigo_tipo_modelo', 'set_x', 'set_y', 'letra', 'id_user', 'id_entidad'];

    public function tipoModelo(): BelongsTo
    {
        return $this->belongsTo(TipoModelo::class, 'codigo_tipo_modelo', 'codigo');
    }
}
