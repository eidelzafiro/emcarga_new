<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCausaLaboral extends Model
{
    protected $table = 'tipos_causas_laborales';

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
