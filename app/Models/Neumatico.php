<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Neumatico extends Model
{
    use SoftDeletes;

    protected $table = 'neumaticos';

    protected $fillable = [
        'id_entidad', 'folio', 'marca', 'modelo', 'medida', 'id_tractivo',
        'fecha_instalacion', 'fecha_retiro', 'kilometraje', 'estado',
        'precio_mn', 'precio_me', 'id_posicion', 'fecha_fabricacion', 'balanceada',
        'profinicial', 'explotacion_anterior', 'kms_promedio',
        'fecha_plan_retiro', 'fecha_plan_aviso',
    ];

    protected function casts(): array
    {
        return [
            'fecha_instalacion' => 'date',
            'fecha_retiro' => 'date',
            'fecha_fabricacion' => 'date',
            'fecha_plan_retiro' => 'date',
            'fecha_plan_aviso' => 'date',
            'kilometraje' => 'decimal:2',
            'kms_promedio' => 'decimal:2',
            'precio_mn' => 'decimal:2',
            'precio_me' => 'decimal:2',
            'profinicial' => 'integer',
            'explotacion_anterior' => 'decimal:2',
            'balanceada' => 'boolean',
        ];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function posicion(): BelongsTo
    {
        return $this->belongsTo(PosicionNeumatico::class, 'id_posicion');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(NeumaticosMovimiento::class, 'id_neumatico');
    }

    public function roturas(): HasMany
    {
        return $this->hasMany(NeumaticosRotura::class, 'id_neumatico');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }
}
