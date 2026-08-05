<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lugare extends Model
{
    use SoftDeletes;

    protected $table = 'lugares';

    protected $fillable = [
        'codigo',
        'nombre',
        'provincia',
        'municipio',
        'direccion',
        'personalidad',
        'latitud',
        'longitud',
        'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}