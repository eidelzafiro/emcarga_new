<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoChofer extends Model
{
    protected $table = 'documentos_chofer';

    protected $fillable = [
        'id_bolsa', 'tipo', 'numero', 'emision', 'vencimiento', 'notas',
        'vigente', 'id_entidad',
    ];

    protected function casts(): array
    {
        return [
            'emision' => 'date',
            'vencimiento' => 'date',
            'vigente' => 'boolean',
        ];
    }

    public const TIPOS = ['LICENCIA', 'CHEQUEO_MEDICO', 'RECALIFICACION', 'PSICOMETRICO'];

    public function bolsa(): BelongsTo
    {
        return $this->belongsTo(Bolsa::class, 'id_bolsa');
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    /**
     * Días restantes para el vencimiento (null si no tiene fecha).
     */
    public function diasParaVencer(): ?int
    {
        return $this->vencimiento ? now()->startOfDay()->diffInDays($this->vencimiento, false) : null;
    }
}
