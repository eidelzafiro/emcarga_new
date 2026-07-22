<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoComponente extends Model
{
    protected $table = 'estados_componentes';

    protected $fillable = ['codigo', 'nombre', 'tipo', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
