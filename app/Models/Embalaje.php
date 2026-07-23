<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Embalaje extends Model
{
    protected $table = 'embalajes';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
