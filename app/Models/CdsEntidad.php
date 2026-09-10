<?php

namespace App\Models;

use App\Models\CatalogoItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Coeficiente CDS (Sistema de Pago por Resultados EMCARGA) por entidad + mes +
 * año + tipo de sistema de pago. Lo introduce manualmente el cliente cada mes;
 * alimenta el reporte "DATOS P/NOMINAS PAGO ADMINISTRATIVO" (SRInicial = STRT × CDS).
 */
class CdsEntidad extends Model
{
    protected $table = 'cds_entidades';

    protected $fillable = [
        'id_entidad',
        'mes',
        'ano',
        'id_tipo_sistema_pago',
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

    public function sistemaPago(): BelongsTo
    {
        return $this->belongsTo(CatalogoItem::class, 'id_tipo_sistema_pago');
    }

    /**
     * CDS vigente para una entidad+mes+año (+sistema de pago opcional).
     * Busca primero la fila del sistema exacto y cae a la fila con sistema
     * NULL (comodín global) si existe; null si no hay ninguna.
     */
    public static function cdsDe(?int $entidadId, int $mes, int $ano, ?int $idTipoSistemaPago = null): ?float
    {
        if (!$entidadId) {
            return null;
        }

        $base = static::query()
            ->where('id_entidad', $entidadId)
            ->where('mes', $mes)
            ->where('ano', $ano);

        if ($idTipoSistemaPago) {
            $row = (clone $base)->where('id_tipo_sistema_pago', $idTipoSistemaPago)->first()
                ?? (clone $base)->whereNull('id_tipo_sistema_pago')->first();
        } else {
            $row = (clone $base)->first();
        }

        return $row ? (float) $row->cds : null;
    }
}
