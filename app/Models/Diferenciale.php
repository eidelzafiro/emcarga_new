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
        'durabilidad', 'relacion', 'ancho', 'cantidad_lubricante', 'cantidad',
        'kms_acumulados', 'capacidad_carter', 'fecha_instalacion', 'fecha_baja',
        'id_lubricante', 'id_tractivo', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
            'fecha_baja' => 'date',
            'durabilidad' => 'integer',
            'relacion' => 'integer',
            'ancho' => 'integer',
            'cantidad_lubricante' => 'integer',
            'cantidad' => 'integer',
            'kms_acumulados' => 'integer',
            'capacidad_carter' => 'integer',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function lubricante(): BelongsTo
    {
        return $this->belongsTo(Lubricante::class, 'id_lubricante');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
