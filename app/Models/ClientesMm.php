<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientesMm extends Model
{
    protected $table = 'clientes_mm';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
