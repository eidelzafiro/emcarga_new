<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HojasRuta extends Model
{
    use SoftDeletes;

    protected $table = 'hojas_ruta';

    protected $fillable = [
        'numero',
        'id_solicitud',
        'id_tractivo',
        'id_entidad',
        'fecha_emision',
        'hora_emision',
        'fecha_cierre',
        'hora_cierre',
        'id_hr_anterior',
        'id_arrastre',
        'id_chofer',
        'id_chofer2',
        'kms_disponible',
        'kms_disponibles_adicionales',
        'kms_totales',
        'combustible_habilitado',
        'combustible_consumido',
        'combustible_tecnico',
        'indice_hr',
        'id_parqueo',
        'id_grupo',
        'id_user',
        'tiempo_mov',
        'tiempo_espera',
        'tiempo_carga',
        'tiempo_taller',
        'tiempo_inactivo',
        'tiempo_otras_actividades',
        'tiempo_total',
        'notas',
        'analisis',
        'dias_trabajados',
        'cancelada',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'cancelada' => 'boolean',
            'fecha_emision' => 'date',
            'fecha_cierre' => 'date',
            'kms_disponible' => 'decimal:2',
            'kms_disponibles_adicionales' => 'decimal:2',
            'kms_totales' => 'decimal:2',
            'combustible_habilitado' => 'decimal:2',
            'combustible_consumido' => 'decimal:2',
            'combustible_tecnico' => 'decimal:2',
            'indice_hr' => 'decimal:8',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function arrastre(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_arrastre');
    }

    public function chofer(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_chofer');
    }

    public function chofer2(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_chofer2');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function parqueo(): BelongsTo
    {
        return $this->belongsTo(Lugare::class, 'id_parqueo');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'id_grupo');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function hrAnterior(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_hr_anterior');
    }

    public function cartasPorte(): HasMany
    {
        return $this->hasMany(CartaPorte::class, 'id_hoja_ruta');
    }
}