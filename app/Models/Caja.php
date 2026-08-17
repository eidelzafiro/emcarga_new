<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caja extends Model
{
    use SoftDeletes;

    protected $table = 'cajas';

    protected $fillable = [
        'id_entidad', 'codigo', 'descripcion', 'marca', 'modelo', 'numero_serie',
        'durabilidad', 'velocidades', 'cantidad_lubricante', 'kms_acumulados',
        'capacidad_carter', 'id_lubricante', 'id_pais', 'fecha_instalacion', 'fecha_baja',
        'id_tractivo', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
            'fecha_baja' => 'date',
            'durabilidad' => 'integer',
            'velocidades' => 'integer',
            'cantidad_lubricante' => 'integer',
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

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'id_pais');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
