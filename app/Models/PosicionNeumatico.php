<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosicionNeumatico extends Model
{
    protected $table = 'posiciones_neumaticos';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
