<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedidaNeumatico extends Model
{
    protected $table = 'medidas_neumaticos';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
