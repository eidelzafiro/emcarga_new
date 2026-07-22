<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Motore extends Model
{
    use SoftDeletes;

    protected $table = 'motores';

    protected $fillable = [
        'codigo', 'descripcion', 'marca', 'modelo', 'numero_serie',
        'id_tractivo', 'estado',
    ];

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MotoresMovimiento::class, 'id_motor');
    }
}
