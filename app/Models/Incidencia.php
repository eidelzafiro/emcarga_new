<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends Model
{
    protected $table = 'incidencias';

    public $timestamps = true;

    protected $fillable = [
        'id_bolsa',
        'id_tipo_incidencia',
        'fecha_inicio',
        'fecha_fin',
        'periodo_actual',
        'importe',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function tipoIncidencia(): BelongsTo
    {
        return $this->belongsTo(TipoIncidencia::class, 'id_tipo_incidencia');
    }
}
