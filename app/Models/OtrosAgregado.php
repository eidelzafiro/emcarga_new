<?php

namespace App\Models;

use App\Models\CatalogoItem;
use App\Models\Tractivo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtrosAgregado extends Model
{
    use SoftDeletes;

    protected $table = 'otros_agregados';

    protected $fillable = [
        'codigo', 'descripcion', 'numero_serie',
            'id_marca', 'id_modelo', 'id_estado', 'id_lubricante',
        'nro_cilindros', 'nro_tiempos', 'caballaje', 'cantidad_lubricante',
        'fecha_baja', 'id_entidad',
        'id_tractivo', 'fecha_instalado', 'km_acumulados', 'km_retirarse', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_baja' => 'date',
            'fecha_instalado' => 'date',
            'km_acumulados' => 'integer',
            'km_retirarse' => 'integer',
        ];
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_marca');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoComponente::class, 'id_estado');
    }

    /**
     * El agregado pertenece a una entidad a través del tractivo asignado
     * (regla 2026-08-28): la entidad se deriva de tractivos.id_entidad.
     */
    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }
}
