<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bolsa extends Model
{
    use SoftDeletes;

    protected $table = 'bolsa';

    protected $fillable = [
        'ci',
        'nombre',
        'apellidos',
        'sexo',
        'color_piel',
        'nivel_educacional',
        'estado_civil',
        'ubicacion_defensa',
        'tiene_licencia',
        'categorias_licencia',
        'licencia_emision',
        'licencia_vencimiento',
        'limitaciones',
        'chequeo_medico_emision',
        'chequeo_medico_vencimiento',
        'reubicacion_emision',
        'reubicacion_vencimiento',
        'psicometrico_emision',
        'psicometrico_vencimiento',
        'fecha_nacimiento',
        'direccion',
        'telefono',
        'email',
        'id_cargo',
        'id_entidad',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'tiene_licencia' => 'boolean',
            'licencia_emision' => 'date',
            'licencia_vencimiento' => 'date',
            'chequeo_medico_emision' => 'date',
            'chequeo_medico_vencimiento' => 'date',
            'reubicacion_emision' => 'date',
            'reubicacion_vencimiento' => 'date',
            'psicometrico_emision' => 'date',
            'psicometrico_vencimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    protected $appends = ['nombrecompleto'];

    public function getNombrecompletoAttribute(): string
    {
        return trim($this->nombre.' '.$this->apellidos);
    }
}
