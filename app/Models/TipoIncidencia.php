<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIncidencia extends Model
{
    protected $table = 'tipos_incidencias';

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
        'id_tipo_deducciones',
        'tsuma',
        'impsuma',
        'penalizacuc',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'id_tipo_deducciones' => 'integer',
            'tsuma' => 'boolean',
            'impsuma' => 'boolean',
            'penalizacuc' => 'boolean',
        ];
    }
}
