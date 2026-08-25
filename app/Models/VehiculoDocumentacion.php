<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VehiculoDocumentacion extends Model
{
    protected $table = 'vehiculos_documentacion';

    protected $fillable = [
        'vehiculo_type', 'vehiculo_id',
        'ficav', 'femision_ficav', 'fvence_ficav',
        'lot', 'femision_lot', 'fvence_lot',
        'circulacion', 'femision_circ', 'fvence_circ',
        'f_reconstruccion',
    ];

    protected $casts = [
        'femision_ficav' => 'date',
        'fvence_ficav' => 'date',
        'femision_lot' => 'date',
        'fvence_lot' => 'date',
        'femision_circ' => 'date',
        'fvence_circ' => 'date',
        'f_reconstruccion' => 'date',
    ];

    public function vehiculo(): MorphTo
    {
        return $this->morphTo();
    }
}
