<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriasProducto extends Model
{
    protected $table = 'categorias_productos';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
