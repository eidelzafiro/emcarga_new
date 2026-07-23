<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoCausasBaja extends Model
{
    protected $table = 'tipos_causas_baja';

    protected $fillable = [
        'codigo',
        'nombre',
        'id_tipo_causa_laboral',
        'activo',
    ];

    public function tipoCausaLaboral(): BelongsTo
    {
        return $this->belongsTo(TipoCausaLaboral::class, 'id_tipo_causa_laboral');
    }

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
