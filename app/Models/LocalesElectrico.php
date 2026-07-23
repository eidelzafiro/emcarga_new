<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalesElectrico extends Model
{
    protected $table = 'locales_electricos';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
