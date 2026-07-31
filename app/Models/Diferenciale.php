<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diferenciale extends Model
{
    use SoftDeletes;

    protected $table = 'diferenciales';

    protected $fillable = [
        'id_entidad', 'codigo', 'descripcion', 'marca', 'modelo', 'numero_serie',
        'id_tractivo', 'estado',
    ];

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
