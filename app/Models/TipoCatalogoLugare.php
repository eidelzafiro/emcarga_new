<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCatalogoLugare extends Model
{
    protected $table = 'tipos_catalogo_lugares';

    protected $fillable = ['codigo', 'nombre', 'abreviatura', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
