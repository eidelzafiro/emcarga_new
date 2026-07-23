<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buque extends Model
{
    protected $table = 'buques';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
