<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de operaciones de sincronización de la API móvil (offline-first).
 */
class ApiSyncLog extends Model
{
    protected $table = 'api_sync_log';

    protected $fillable = [
        'user_id',
        'direction',
        'endpoint',
        'id_entidad',
        'fecha_operaciones',
        'items',
        'payload_hash',
    ];

    protected function casts(): array
    {
        return [
            'fecha_operaciones' => 'date',
            'items' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
