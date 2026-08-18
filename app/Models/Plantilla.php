<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plantilla extends Model
{
    protected $table = 'plantilla';

    protected $fillable = [
        'id_cargo',
        'id_area',
        'aprobada',
        'cubierta',
        'cubierta2',
        'propuesta',
        'v_necesidad',
        'necesidad',
        'id_entidad',
    ];

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
