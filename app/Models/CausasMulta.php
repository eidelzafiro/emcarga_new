<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CausasMulta extends Model
{
    protected $table = 'causas_multas';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
