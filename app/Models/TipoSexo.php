<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSexo extends Model
{
    protected $table = 'tipos_sexo';

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
