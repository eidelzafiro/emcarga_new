<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoNeumatico extends Model
{
    protected $table = 'tipos_neumaticos';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
