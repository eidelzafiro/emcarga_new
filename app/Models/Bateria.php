<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bateria extends Model
{
    use SoftDeletes;

    protected $table = 'baterias';

    protected $fillable = [
        'id_entidad', 'folio', 'marca', 'modelo', 'id_tractivo',
        'fecha_instalacion', 'fecha_retiro', 'estado',
        'voltaje', 'amperaje', 'precio_mn', 'precio_me', 'id_motivo_baja', 'id_destino',
        'fecha_movimiento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
            'fecha_retiro' => 'date',
            'fecha_movimiento' => 'date',
            'precio_mn' => 'decimal:2',
            'precio_me' => 'decimal:2',
            'voltaje' => 'integer',
            'amperaje' => 'integer',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function motivoBaja(): BelongsTo
    {
        return $this->belongsTo(MotivosBajaBaterium::class, 'id_motivo_baja');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(DestinoAgregado::class, 'id_destino');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(BateriasMovimiento::class, 'id_bateria');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
