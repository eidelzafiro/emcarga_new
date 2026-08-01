<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modelo extends Model
{
    protected $fillable = ['codigo', 'nombre', 'tipo', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
