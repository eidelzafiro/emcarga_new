<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaCargo extends Model
{
    protected $table = 'categorias_cargo';

    protected $fillable = ['codigo', 'nombre', 'abreviatura', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
