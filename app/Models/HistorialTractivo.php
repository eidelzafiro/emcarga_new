<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialTractivo extends Model
{
    protected $table = 'historial_tractivos';

    protected $fillable = ['id_tractivo', 'id_grupo', 'id_caja', 'id_motor', 'id_diferencial', 'id_unidad', 'fecha_cierre', 'km_historico', 'km_motor', 'km_caja', 'km_diferencial', 'indice', 'indice_acumulado', 'plan_combustible', 'gps'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }
}
