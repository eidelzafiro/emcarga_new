<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bolsa extends Model
{
    use SoftDeletes;

    protected $table = 'bolsa';

    protected $fillable = [
        'ci',
        'nombre',
        'apellidos',
        'sexo',
        'fecha_nacimiento',
        'direccion',
        'telefono',
        'email',
        'id_cargo',
        'id_entidad',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
