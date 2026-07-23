<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementosGasto extends Model
{
    protected $table = 'elementos_gasto';

    protected $fillable = ['codigo', 'nombre', 'subelemento', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
