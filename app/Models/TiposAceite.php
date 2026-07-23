<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiposAceite extends Model
{
    protected $table = 'tipos_aceites';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
