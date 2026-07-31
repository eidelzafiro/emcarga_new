<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoTasa extends Model
{
    protected $table = 'tipos_tasas';

    protected $fillable = [
        'id_entidad', 'codigo',
        'nombre',
        'unidad',
        'valor',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'valor' => 'decimal:4',
        ];
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
