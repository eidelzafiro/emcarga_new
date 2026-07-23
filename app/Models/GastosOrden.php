<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastosOrden extends Model
{
    protected $table = 'gastos_orden';

    protected $fillable = ['id_orden_taller', 'nombre', 'cantidad', 'codigo_pieza', 'vale', 'motivo', 'id_motor'];
}
