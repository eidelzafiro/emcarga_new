<?php

namespace App\Models;

use App\Models\Arrastre;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoVehiculo extends Model
{
    protected $table = 'tipo_vehiculos';

    protected $fillable = [
        'id_tipo_equipo', 'id_marca', 'id_modelo', 'id_tipo_mantenimiento',
        'id_tipo_tractivo', 'id_tipo_arrastre', 'clase', 'fabricacion', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class, 'id_tipo_equipo');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_marca');
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_modelo');
    }

    public function tipoMantenimiento(): BelongsTo
    {
        return $this->belongsTo(TiposMantenimiento::class, 'id_tipo_mantenimiento');
    }

    public function tipoTractivo(): BelongsTo
    {
        return $this->belongsTo(TipoTractivo::class, 'id_tipo_tractivo');
    }

    public function tipoArrastre(): BelongsTo
    {
        return $this->belongsTo(TipoArrastre::class, 'id_tipo_arrastre');
    }

    public function tractivos(): HasMany
    {
        return $this->hasMany(Tractivo::class, 'id_tipo_vehiculo');
    }

    public function arrastres(): HasMany
    {
        return $this->hasMany(Arrastre::class, 'id_tipo_vehiculo');
    }
}
