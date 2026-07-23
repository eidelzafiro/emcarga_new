<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedioProteccion extends Model
{
    protected $table = 'medios_proteccion';

    protected $fillable = [
        'nombre',
        'id_tipo_medio_proteccion',
        'duracion',
        'tipo_duracion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function tipoMedioProteccion(): BelongsTo
    {
        return $this->belongsTo(TipoMedioProteccion::class, 'id_tipo_medio_proteccion');
    }
}
