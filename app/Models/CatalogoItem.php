<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogoItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tipo',
        'origen_id',
        'codigo',
        'nombre',
        'id_pais',
        'logo',
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

    public function pais(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_pais');
    }

    /**
     * Incidencias de este tipo (cuando el ítem es del tipo tipos_incidencias).
     */
    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class, 'id_tipo_incidencia');
    }

    public function scopeTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }
}
