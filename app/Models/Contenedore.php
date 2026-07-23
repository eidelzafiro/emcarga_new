<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contenedore extends Model
{
    protected $table = 'contenedores';

    protected $fillable = ['codigo', 'id_carta_porte', 'id_carta_porte_retorno', 'fecha_salida', 'fecha_retorno', 'tipo', 'tara', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
