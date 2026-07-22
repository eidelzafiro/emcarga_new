<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    protected $table = 'bitacora';

    protected $fillable = [
        'user_id',
        'accion',
        'tabla',
        'id_registro',
        'detalles',
        'ip_address',
        'fecha_accion',
    ];

    protected $casts = ['fecha_accion' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra una acción en la bitácora de auditoría.
     * Si no se indica usuario/IP, toma los del request actual.
     */
    public static function registrar(string $accion, ?string $detalles = null, ?int $userId = null, ?string $ip = null): self
    {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'accion' => $accion,
            'detalles' => $detalles,
            'ip_address' => $ip ?? request()->ip(),
            'fecha_accion' => now(),
        ]);
    }
}
