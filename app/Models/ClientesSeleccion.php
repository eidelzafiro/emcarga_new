<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientesSeleccion extends Model
{
    protected $table = 'clientes_seleccion';

    protected $fillable = ['nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
