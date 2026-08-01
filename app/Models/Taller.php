<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Taller extends Model
{
    protected $table = 'talleres';

    protected $fillable = ['codigo', 'nombre', 'id_entidad', 'activo'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    public function naves(): HasMany
    {
        return $this->hasMany(Nave::class, 'id_taller');
    }
}
