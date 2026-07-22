<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

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
        'idperfil',
        'idunidad',
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

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class, 'idperfil');
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
