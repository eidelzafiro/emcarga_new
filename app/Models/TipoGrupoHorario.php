<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoGrupoHorario extends Model
{
    protected $table = 'tipos_grupo_horario';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
