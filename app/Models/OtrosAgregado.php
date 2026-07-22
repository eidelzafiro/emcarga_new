<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtrosAgregado extends Model
{
    use SoftDeletes;

    protected $table = 'otros_agregados';

    protected $fillable = [
        'codigo', 'descripcion', 'numero_serie',
        'id_marca', 'id_modelo', 'id_pais', 'id_estado', 'id_lubricante',
        'nro_cilindros', 'nro_tiempos', 'caballaje', 'cantidad_lubricante',
        'fecha_baja',
    ];

    protected function casts(): array
    {
        return ['fecha_baja' => 'date'];
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'id_marca');
    }
}
