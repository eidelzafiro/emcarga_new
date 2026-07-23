<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivosBajaBaterium extends Model
{
    protected $table = 'motivos_baja_bateria';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
