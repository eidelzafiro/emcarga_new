<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogoTipo extends Model
{
    protected $fillable = [
        'tipo',
        'titulo',
        'agrupacion',
        'activo',
        'orden',
        'tabla_legacy',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CatalogoItem::class, 'tipo', 'tipo');
    }
}
