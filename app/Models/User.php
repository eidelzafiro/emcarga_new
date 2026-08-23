<?php

namespace App\Models;

use App\Mail\RestablecerPassword;
use App\Support\MailRouter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
        'apellidos',
        'username',
        'email',
        'avatar',
        'password',
        'id_entidad',
        'fecha_operaciones',
        'idgrupo',
        'bloqueado',
        'intentos_fallidos',
        'ultimo_login',
        'fecha_cambio_password',
        'password_temporal',
        'activo',
        'two_factor_secret',
        'two_factor_recovery',
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
            'activo' => 'boolean',
            'intentos_fallidos' => 'integer',
            'ultimo_login' => 'datetime',
            'fecha_cambio_password' => 'datetime',
            'fecha_operaciones' => 'date',
            'two_factor_recovery' => 'array',
        ];
    }

    /**
     * Indica si el usuario tiene un rol privilegiado que exige 2FA.
     */
    public function esPrivilegiado(): bool
    {
        return $this->hasAnyRole(['SUPERADMIN', 'CONFIGURACIONES']);
    }

    /**
     * Nombre completo para mostrar: nombre + apellidos (si existen).
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim($this->name.' '.($this->apellidos ?? ''));
    }

    /**
     * URL pública del avatar (null si no tiene: la UI muestra iniciales).
     */
    public function getUrlAvatarAttribute(): ?string
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }

    /**
     * Accesor: true si el usuario tiene 2FA habilitado.
     */
    public function getTwoFactorEnabledAttribute(): bool
    {
        return ! empty($this->two_factor_secret);
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
    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    /**
     * Entidades a las que el usuario tiene acceso (pivote multi-entidad).
     */
    public function entidades(): BelongsToMany
    {
        return $this->belongsToMany(Entidad::class, 'entidad_user')->withTimestamps();
    }

    /**
     * Entidades que el usuario puede seleccionar como contexto de trabajo:
     * su propia entidad + las subordinadas en la jerarquía (para ADMIN
     * también). La entidad principal siempre se incluye.
     *
     * @return Collection<int, Entidad>
     */
    public function entidadesAcceso(): Collection
    {
        if (! $this->id_entidad) {
            return collect();
        }

        $ids = Entidad::idsPermitidos($this->id_entidad);

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

    /**
     * Envía el correo de restablecimiento de contraseña eligiendo el
     * transporte según el dominio: ".cu" → SMTP nacional, resto → Gmail
     * (con caída al mailer por defecto si faltan credenciales).
     */
    public function sendPasswordResetNotification($token): void
    {
        if (empty($this->email)) {
            return;
        }

        $transporte = MailRouter::paraEmail($this->email);
        $url = url(route('password.reset', ['token' => $token, 'email' => $this->email]));

        Mail::mailer($transporte)
            ->to($this->email)
            ->send(new RestablecerPassword($this, $url, $transporte));
    }
}
