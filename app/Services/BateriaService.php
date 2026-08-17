<?php

namespace App\Services;

use App\Models\Bateria;
use App\Models\BateriasMovimiento;

/**
 * Lógica de negocio de baterías (réplica del legacy CI3 ModBaterias).
 *
 * - Toda alta genera un movimiento inicial (estado de la batería).
 * - Un solo movimiento vigente por batería (fecha_retiro NULL); al mover se
 *   cierra el vigente (fecha_retiro + tiempo_trabajo) y se abre otro.
 * - Baja con motivo obligatorio (motivos_baja_bateria) y destino de baja.
 */
class BateriaService
{
    public const DESTINO_VEHICULO = 1;

    /**
     * Registra un movimiento (montar/desmontar/cambiar de vehículo).
     */
    public function registrarMovimiento(
        Bateria $bateria,
        int $idTractivo,
        ?string $fechaMovimiento,
        ?int $idDestino,
        ?string $observaciones = null
    ): BateriasMovimiento {
        $fecha = $fechaMovimiento ?? now()->toDateString();
        $idDestino = $idDestino ?? self::DESTINO_VEHICULO;

        // Cerrar movimiento vigente (fecha_retiro NULL)
        $vigente = $bateria->movimientos()->whereNull('fecha_retiro')->orderByDesc('id')->first();
        if ($vigente) {
            $dias = \Carbon\Carbon::parse($vigente->fecha_movimiento ?? $fecha)->diffInDays(\Carbon\Carbon::parse($fecha));
            $vigente->update([
                'fecha_retiro' => $fecha,
                'tiempo_trabajo' => (int) round($dias / 30),
            ]);
        }

        $movimiento = $bateria->movimientos()->create([
            'id_tractivo' => (int) $idDestino === self::DESTINO_VEHICULO ? $idTractivo : null,
            'fecha_movimiento' => $fecha,
            'tipo' => 'movimiento',
            'id_destino' => $idDestino,
            'observaciones' => $observaciones,
            'id_entidad' => $bateria->id_entidad,
        ]);

        // Actualizar cabecera
        $bateria->update([
            'id_tractivo' => (int) $idDestino === self::DESTINO_VEHICULO ? $idTractivo : null,
            'fecha_movimiento' => $fecha,
            'id_destino' => $idDestino,
        ]);

        return $movimiento;
    }

    /**
     * Da de baja una batería con motivo obligatorio.
     */
    public function darDeBaja(Bateria $bateria, ?string $fechaBaja, int $idMotivoBaja, ?int $idDestino = null): void
    {
        $fecha = $fechaBaja ?? now()->toDateString();

        // Cerrar movimiento vigente
        $vigente = $bateria->movimientos()->whereNull('fecha_retiro')->orderByDesc('id')->first();
        if ($vigente) {
            $vigente->update(['fecha_retiro' => $fecha]);
        }

        $bateria->update([
            'fecha_retiro' => $fecha,
            'estado' => 'baja',
            'id_motivo_baja' => $idMotivoBaja,
            'id_destino' => $idDestino ?: null,
        ]);
    }
}
