<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cargo extends Model
{
    use SoftDeletes;

    protected $table = 'cargos';

    protected $fillable = [
        'codigo',
        'nombre',
        'funciones',
        'medios_requeridos',
        'competencias',
        'es_chofer',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_chofer' => 'boolean',
            'activo' => 'boolean',
        ];
    }
}
