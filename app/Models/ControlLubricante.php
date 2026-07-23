<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlLubricante extends Model
{
    protected $table = 'control_lubricantes';

    protected $fillable = ['id_tractivo', 'id_lubricante', 'fecha_cambio', 'cantidad_litros', 'kilometraje', 'observaciones', 'id_orden_taller', 'confeccionado_por'];

    protected function casts(): array
    {
        return ['fecha_cambio' => 'date'];
    }

    public function tractivo()
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function lubricante()
    {
        return $this->belongsTo(Lubricante::class, 'id_lubricante');
    }
}
