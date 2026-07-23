<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroOrdenesTaller extends Model
{
    protected $table = 'registro_ordenes_taller';

    protected $fillable = ['id_tractivo', 'fecha_salida_taller', 'tiempo_minutos', 'observaciones'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
