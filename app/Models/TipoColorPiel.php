<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoColorPiel extends Model
{
    protected $table = 'tipos_color_piel';

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
