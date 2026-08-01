<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Firma extends Model
{
    protected $fillable = [
        'nombre',
        'id_entidad',
        'confecciona_nombre',
        'confecciona_cargo',
        'revisa_nombre',
        'revisa_cargo',
        'aprueba_nombre',
        'aprueba_cargo',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
