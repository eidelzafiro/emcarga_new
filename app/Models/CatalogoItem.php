<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class CatalogoItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tipo',
        'codigo',
        'nombre',
        'activo',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'extra' => 'array',
        ];
    }

    public function scopeTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }
}
