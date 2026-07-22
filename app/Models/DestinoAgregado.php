<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DestinoAgregado extends Model
{
    protected $table = 'destinos_agregados';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
