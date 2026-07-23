<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CausasGp extends Model
{
    protected $table = 'causas_gps';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
