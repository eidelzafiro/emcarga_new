<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Coeficiente CDS (Sistema de Pago por Resultados EMCARGA) por entidad + mes + año.
 * Lo introduce manualmente el cliente cada mes; alimenta el reporte
 * "DATOS P/NOMINAS PAGO ADMINISTRATIVO" (SRInicial = STRT × CDS).
 */
class CdsEntidad extends Model
{
    protected $table = 'cds_entidades';

    protected $fillable = [
        'id_entidad',
        'mes',
        'ano',
        'cds',
        'id_user',
    ];

    protected function casts(): array
    {
        return [
            'mes' => 'integer',
            'ano' => 'integer',
            'cds' => 'float',
        ];
    }

    public function entidad(): BelongsTo
    {
        return $this->belongsTo(Entidad::class, 'id_entidad');
    }

    /**
     * CDS vigente para una entidad+mes+año (exacto); null si no existe.
     */
    public static function cdsDe(?int $entidadId, int $mes, int $ano): ?float
    {
        if (!$entidadId) {
            return null;
        }

        $row = static::query()
            ->where('id_entidad', $entidadId)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->first();

        return $row ? (float) $row->cds : null;
    }
}
