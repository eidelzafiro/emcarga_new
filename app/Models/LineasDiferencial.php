<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LineasDiferencial extends Model
{
    protected $table = 'lineas_diferencial';

    protected $fillable = ['id_tarjetero', 'id_lubricante', 'durabilidad', 'ancho', 'relacion', 'litros'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
