<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nave extends Model
{
    protected $fillable = ['id_entidad', 'codigo', 'nombre', 'ubicacion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function vallas(): HasMany
    {
        return $this->hasMany(Valla::class, 'id_nave');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
