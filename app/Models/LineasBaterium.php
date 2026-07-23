<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LineasBaterium extends Model
{
    protected $table = 'lineas_bateria';

    protected $fillable = ['id_tarjetero', 'amperaje', 'voltaje', 'largo', 'ancho', 'alto', 'durabilidad'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function tarjetero(): BelongsTo
    {
        return $this->belongsTo(Tarjetero::class, 'id_tarjetero');
    }
}
