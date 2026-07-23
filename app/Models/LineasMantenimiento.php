<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineasMantenimiento extends Model
{
    protected $table = 'lineas_mantenimiento';

    protected $fillable = ['id_tipo_mantenimiento', 'kilometraje', 'descripcion'];
}
