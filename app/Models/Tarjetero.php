<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarjetero extends Model
{
    protected $table = 'tarjetero';

    protected $fillable = ['codigo', 'nombre', 'tipo_linea', 'id_marca', 'id_modelo', 'id_pais', 'existencia', 'precio_mn', 'precio_me', 'valor_mn', 'valor_me', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
