<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoIncidencia extends Model
{
    protected $table = 'tipos_incidencias';

    public function tipoDeduccione(): BelongsTo
    {
        return $this->belongsTo(TipoDeduccione::class, 'id_tipo_deducciones');
    }

    protected $fillable = [
        'codigo',
        'nombre',
        'activo',
        'id_tipo_deducciones',
        'tsuma',
        'impsuma',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'id_tipo_deducciones' => 'integer',
            'tsuma' => 'boolean',
            'impsuma' => 'boolean',
        ];
    }
}
