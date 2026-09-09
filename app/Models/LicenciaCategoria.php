<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenciaCategoria extends Model
{
    protected $table = 'licencia_categorias';

    protected $fillable = ['id_bolsa', 'categoria'];

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }
}
