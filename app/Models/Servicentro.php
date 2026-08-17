<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servicentro extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'codigo',
        'ubicacion',
        'id_provincia',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'id_provincia');
    }
}
