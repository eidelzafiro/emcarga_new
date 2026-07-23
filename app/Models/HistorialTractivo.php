<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialTractivo extends Model
{
    protected $table = 'historial_tractivos';

    protected $fillable = ['id_tractivo', 'id_grupo', 'id_caja', 'id_motor', 'id_diferencial', 'id_unidad', 'fecha_cierre', 'km_historico', 'km_motor', 'km_caja', 'km_diferencial', 'indice', 'indice_acumulado', 'plan_combustible', 'gps'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
