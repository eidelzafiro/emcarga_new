<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penalizacion extends Model
{
    protected $table = 'penalizaciones';

    public $timestamps = true;

    protected $fillable = [
        'id_bolsa',
        'id_tipo_penalizacion',
        'id_area_penalizada',
        'id_pago_adicional',
        'fecha',
        'importe',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function tipoPenalizacion(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_tipo_penalizacion');
    }

    public function areaPenalizada(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'id_area_penalizada');
    }

    public function pagoAdicional(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_pago_adicional');
    }
}
