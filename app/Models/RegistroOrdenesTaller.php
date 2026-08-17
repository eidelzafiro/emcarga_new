<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroOrdenesTaller extends Model
{
    protected $table = 'registro_ordenes_taller';

    protected $fillable = ['id_tractivo', 'fecha_salida_taller', 'tiempo_minutos', 'observaciones'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }
}
