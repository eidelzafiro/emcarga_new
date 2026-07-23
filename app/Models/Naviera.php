<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Naviera extends Model
{
    protected $table = 'navieras';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
