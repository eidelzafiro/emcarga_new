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
        return $this->belongsTo(TipoPenalizacione::class, 'id_tipo_penalizacion');
    }
}
