<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de nómina (paridad legacy rh_movimientos / rh_hmovimientos).
 * Un mismo id legacy puede existir en ambas tablas (origen 'mov'/'hmov');
 * la PK `id` las desambigua.
 */
class MovimientoRrhh extends Model
{
    protected $table = 'movimientos_rrhh';

    protected $fillable = [
        'origen', 'id_legacy', 'id_bolsa', 'nronomina', 'id_tractivo',
        'id_plantilla', 'fbaja', 'cubreplaza', 'tipomov', 'id_user',
    ];

    protected $casts = [
        'id_legacy' => 'integer',
        'id_bolsa' => 'integer',
        'nronomina' => 'integer',
        'id_tractivo' => 'integer',
        'id_plantilla' => 'integer',
        'fbaja' => 'date',
        'cubreplaza' => 'integer',
        'id_user' => 'integer',
    ];

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(Plantilla::class, 'id_plantilla');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function scopeMov(Builder $query): Builder
    {
        return $query->where('origen', 'mov');
    }

    public function scopeHmov(Builder $query): Builder
    {
        return $query->where('origen', 'hmov');
    }
}
