<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CombustibleLubricante extends Model
{
    protected $table = 'combustibles_lubricantes';

    protected $fillable = [
        'id_carga',
        'id_tractivo',
        'id_tipo_lubricante',
        'id_causa',
        'fecha',
        'folio',
        'cantidad',
        'importe_mn',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad' => 'decimal:2',
            'importe_mn' => 'decimal:2',
        ];
    }

    public function carga(): BelongsTo
    {
        return $this->belongsTo(CombustibleCarga::class, 'id_carga');
    }

    public function tractivo(): BelongsTo
    {
        return $this->belongsTo(Tractivo::class, 'id_tractivo');
    }

    public function tipoLubricante(): BelongsTo
    {
        return $this->belongsTo(TipoLubricante::class, 'id_tipo_lubricante');
    }

    public function causa(): BelongsTo
    {
        return $this->belongsTo(TipoCausa::class, 'id_causa');
    }
}
