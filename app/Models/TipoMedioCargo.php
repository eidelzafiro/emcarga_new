<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoMedioCargo extends Model
{
    protected $table = 'tipos_medios_cargo';

    protected $fillable = [
        'id_medio_proteccion',
        'id_cargo',
    ];

    public function medioProteccion(): BelongsTo
    {
        return $this->belongsTo(MedioProteccion::class, 'id_medio_proteccion');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }
}
