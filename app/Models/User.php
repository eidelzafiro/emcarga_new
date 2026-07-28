<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Máximo de intentos fallidos antes del bloqueo automático (regla legacy).
     */
    public const MAX_INTENTOS_LOGIN = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'id_entidad',
        'fecha_operaciones',
        'idgrupo',
        'bloqueado',
        'intentos_fallidos',
        'ultimo_login',
        'fecha_cambio_password',
        'password_temporal',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'bloqueado' => 'boolean',
            'password_temporal' => 'boolean',
            'intentos_fallidos' => 'integer',
            'ultimo_login' => 'datetime',
            'fecha_cambio_password' => 'datetime',
            'fecha_operaciones' => 'date',
        ];
    }

    /**
     * Indica si el usuario está bloqueado: manualmente por el administrador
     * o por superar el máximo de intentos fallidos (regla legacy).
     */
    public function estaBloqueado(): bool
    {
        return $this->bloqueado || $this->intentos_fallidos >= self::MAX_INTENTOS_LOGIN;
    }

    /**
     * Entidad principal del usuario (su "unidad" en el legacy).
     */
    public function entidad(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    /**
     * Entidades a las que el usuario tiene acceso (pivote multi-entidad).
     */
    public function entidades(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Entidad::class, 'entidad_user')->withTimestamps();
    }

    /**
     * Entidades que el usuario puede seleccionar como contexto de trabajo:
     * su propia entidad + las subordinadas en la jerarquía (para ADMIN
     * también). La entidad principal siempre se incluye.
     *
     * @return \Illuminate\Support\Collection<int, Entidad>
     */
    public function entidadesAcceso(): \Illuminate\Support\Collection
    {
        if (! $this->id_entidad) {
            return collect();
        }

        $ids = collect(Entidad::subEntidadesIds($this->id_entidad))
            ->push($this->id_entidad)
            ->unique()
            ->values()
            ->all();

        $porJerarquia = Entidad::whereIn('id', $ids)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        if ($this->hasAnyRole(['SUPERADMIN', 'CONFIGURACIONES'])) {
            return $porJerarquia;
        }

        $adicionales = $this->entidades()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return $porJerarquia
            ->merge($adicionales)
            ->unique('id')
            ->values();
    }

    /**
     * Verifica si el usuario puede trabajar con una entidad dada.
     */
    public function tieneAccesoAEntidad(?int $entidadId): bool
    {
        if (! $entidadId) {
            return false;
        }

        return $this->entidadesAcceso()->contains('id', $entidadId);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class);
    }
}
