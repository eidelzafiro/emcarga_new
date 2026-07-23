<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivosEntradaTaller extends Model
{
    protected $table = 'motivos_entrada_taller';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
