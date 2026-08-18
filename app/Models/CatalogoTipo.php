<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogoTipo extends Model
{
    protected $fillable = [
        'tipo',
        'titulo',
        'agrupacion',
        'activo',
        'orden',
        'tabla_legacy',
        'fields',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fields' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogoItem::class, 'tipo', 'tipo');
    }
}
