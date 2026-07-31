<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnosComerciale extends Model
{
    protected $fillable = ['nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
