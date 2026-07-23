<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSuspension extends Model
{
    protected $table = 'tipos_suspension';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
