<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineasNeumatico extends Model
{
    protected $table = 'lineas_neumatico';

    protected $fillable = ['id_tarjetero', 'id_tipo_neumatico', 'id_medida_neumatico', 'capas', 'presion', 'carga', 'velocidad', 'durabilidad', 'regrabable', 'camara'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
