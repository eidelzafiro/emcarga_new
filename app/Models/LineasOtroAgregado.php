<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineasOtroAgregado extends Model
{
    protected $table = 'lineas_otro_agregado';

    protected $fillable = ['id_tarjetero', 'id_tipo_agregado', 'durabilidad'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
