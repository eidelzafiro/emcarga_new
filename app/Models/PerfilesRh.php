<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfilesRh extends Model
{
    protected $table = 'perfiles_rh';

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
