<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoModelo extends Model
{
    protected $table = 'tipos_modelo';

    protected $fillable = ['codigo', 'nombre', 'ancho', 'alto', 'activo', 'id_entidad'];
}
