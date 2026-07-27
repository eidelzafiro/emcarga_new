<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entidad extends Model
{
    use SoftDeletes;

    protected $table = 'entidades';

    protected $fillable = [
        'codigo',
        'nombre',
        'abreviatura',
        'id_area',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    /**
     * Usuarios que tienen esta entidad como principal.
     */
    public function usuarios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'id_entidad');
    }

    /**
     * Usuarios con acceso a esta entidad vía pivote multi-entidad.
     */
    public function usuariosConAcceso(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'entidad_user')->withTimestamps();
    }
}
