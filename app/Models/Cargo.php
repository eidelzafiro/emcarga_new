<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cargo extends Model
{
    use SoftDeletes;

    protected $table = 'cargos';

    protected $fillable = [
        'id_entidad',
        'codigo',
        'nombre',
        'funciones',
        'medios_requeridos',
        'competencias',
        'es_chofer',
        'activo',
        'id_calificador',
        'id_fondo_tiempo',
        'id_nivel_educacion',
        'id_grupo_escala',
        'id_clasificacion_laboral',
        'id_categoria_cargo',
        'id_grupo_horario',
        'tipo_salario',
        'en_salario',
        'tarifa',
        'salario_escala',
        'cla',
        'noct1',
        'noct2',
        'pago_adicional',
        'aseo_tecnologico',
    ];

    protected function casts(): array
    {
        return [
            'es_chofer' => 'boolean',
            'activo' => 'boolean',
            'tipo_salario' => 'integer',
            'en_salario' => 'integer',
            'aseo_tecnologico' => 'boolean',
        ];
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function calificador(): BelongsTo
    {
        return $this->belongsTo(Calificador::class, 'id_calificador');
    }

    public function fondo_tiempo(): BelongsTo
    {
        return $this->belongsTo(FondoTiempo::class, 'id_fondo_tiempo');
    }

    public function nivel_educacion(): BelongsTo
    {
        return $this->belongsTo(TipoNivelEducacion::class, 'id_nivel_educacion');
    }

    public function grupo_escala(): BelongsTo
    {
        return $this->belongsTo(GrupoEscala::class, 'id_grupo_escala');
    }

    public function clasificacion_laboral(): BelongsTo
    {
        return $this->belongsTo(TipoClasificacionLaboral::class, 'id_clasificacion_laboral');
    }

    public function categoria_cargo(): BelongsTo
    {
        return $this->belongsTo(CategoriaCargo::class, 'id_categoria_cargo');
    }

    public function grupo_horario(): BelongsTo
    {
        return $this->belongsTo(TipoGrupoHorario::class, 'id_grupo_horario');
    }
}
