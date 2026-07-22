<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medidore extends Model
{
    protected $table = 'medidores';

    protected $fillable = [
        'codigo', 'ruta_folio', 'metro', 'prepago', 'tipo',
        'lectura_actual', 'factor', 'lecturas_mensuales', 'id_unidad', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'prepago' => 'boolean',
            'lectura_actual' => 'decimal:2',
            'factor' => 'decimal:2',
            'lecturas_mensuales' => 'json',
            'activo' => 'boolean',
        ];
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(LecturasMedidore::class, 'id_medidor');
    }
}
