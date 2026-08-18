<?php

namespace App\Services;

use App\Models\GastosOrden;
use App\Models\MovimientosTaller;
use App\Models\OrdenesOperacione;
use App\Models\OrdenesTaller;
use App\Models\Tractivo;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de Órdenes de Taller (réplica del legacy CI3 ModTaller).
 *
 * - Una sola OT abierta por vehículo (validación al crear).
 * - Estados derivados: abierta (sin fecha_salida), cerrada (con fecha_salida),
 *   cancelada (flag).
 * - Transición de estado del tractivo al abrir/cerrar la OT (EN TALLER/PARALIZADO
 *   → ACTIVO).
 * - Operaciones con hasta 3 operarios y cálculo de tiempo.
 * - Piezas/recursos de almacén (gastos_orden) con copia del motor.
 * - Movimientos en taller (nave/valla).
 */
class OrdenTallerService
{
    /**
     * Crea una OT validando que el vehículo no tenga otra OT abierta.
     */
    public function crear(array $datos, int $idEntidad): OrdenesTaller
    {
        $abierta = OrdenesTaller::where('id_tractivo', $datos['id_tractivo'])
            ->whereNull('fecha_salida')->where('cancelada', false)->first();
        if ($abierta) {
            throw new \InvalidArgumentException('EXISTE UNA ORDEN ABIERTA VINCULADA A ESE VEHÍCULO. CIERRE PRIMERO LA ORDEN.');
        }

        $ot = OrdenesTaller::create([
            'numero' => $datos['numero'] ?? $this->siguienteNumero($idEntidad),
            'id_tractivo' => $datos['id_tractivo'],
            'id_tipo_mantenimiento' => $datos['id_tipo_mantenimiento'] ?? null,
            'id_motivo_entrada' => $datos['id_motivo_entrada'] ?? null,
            'id_clasificacion' => $datos['id_clasificacion'] ?? null,
            'fecha_ingreso' => $datos['fecha_ingreso'] ?? now()->toDateString(),
            'hora_ingreso' => $datos['hora_ingreso'] ?? null,
            'fecha_salida' => $datos['fecha_salida'] ?? null,
            'hora_salida' => $datos['hora_salida'] ?? null,
            'id_reporte' => $datos['id_reporte'] ?? null,
            'id_confeccionado' => $datos['id_confeccionado'] ?? null,
            'id_operario' => $datos['id_operario'] ?? null,
            'notas' => $datos['notas'] ?? null,
            'cancelada' => false,
            'ot_largo_plazo' => $datos['ot_largo_plazo'] ?? null,
            'combtaller' => $datos['combtaller'] ?? 0,
            'id_motor' => $datos['id_motor'] ?? null,
            'id_taller' => $datos['id_taller'] ?? null,
            'id_entidad' => $idEntidad,
            'id_unidad' => $idEntidad,
            'estado' => 'abierta',
            'kilometraje' => $datos['kilometraje'] ?? null,
        ]);

        $this->aplicarEstadoTractivo($ot, true);

        return $ot;
    }

    /**
     * Cierra una OT (establece fecha/hora de salida) y pasa el tractivo a ACTIVO.
     */
    public function cerrar(OrdenesTaller $ot, ?string $fechaSalida, ?string $horaSalida): OrdenesTaller
    {
        $ot->update([
            'fecha_salida' => $fechaSalida ?? now()->toDateString(),
            'hora_salida' => $horaSalida ?? null,
            'ottiempo' => $ot->calcularTiempoTotal(),
            'estado' => 'cerrada',
        ]);
        $this->aplicarEstadoTractivo($ot, false);

        return $ot;
    }

    /**
     * Cancela una OT (flag cancelada).
     */
    public function cancelar(OrdenesTaller $ot): OrdenesTaller
    {
        $ot->update(['cancelada' => true, 'estado' => 'cancelada']);
        $this->aplicarEstadoTractivo($ot, false);

        return $ot;
    }

    /**
     * Registra una operación con hasta 3 operarios y calcula el tiempo.
     */
    public function agregarOperacion(OrdenesTaller $ot, array $datos): OrdenesOperacione
    {
        $tiempo = $this->calcularTiempo($datos['fecha_inicio'] ?? null, $datos['hora_inicio'] ?? null, $datos['fecha_final'] ?? null, $datos['hora_final'] ?? null);

        $op = $ot->operaciones()->create([
            'id_tipo_operacion' => $datos['id_tipo_operacion'] ?? null,
            'id_operario' => $datos['id_operario'] ?? null,
            'id_operario2' => $datos['id_operario2'] ?? null,
            'id_operario3' => $datos['id_operario3'] ?? null,
            'fecha_inicio' => $datos['fecha_inicio'] ?? null,
            'hora_inicio' => $datos['hora_inicio'] ?? null,
            'fecha_final' => $datos['fecha_final'] ?? null,
            'hora_final' => $datos['hora_final'] ?? null,
            'tiempo' => $tiempo,
            'id_nave' => $datos['id_nave'] ?? null,
            'id_valla' => $datos['id_valla'] ?? null,
            'id_entidad' => $ot->id_entidad,
        ]);

        // Recalcular ottiempo con la suma de tiempos de operaciones
        $total = (float) $ot->operaciones()->sum('tiempo');
        $ot->update(['ottiempo' => $ot->ottiempo + $tiempo]);

        return $op;
    }

    /**
     * Registra una pieza/recurso de almacén en la OT (copia el motor del tractivo).
     */
    public function agregarGasto(OrdenesTaller $ot, array $datos): GastosOrden
    {
        $idMotor = $datos['id_motor'] ?? $ot->id_motor ?? ($ot->tractivo?->id_motor);

        return $ot->gastos()->create([
            'importe_me' => $datos['importe_me'] ?? 0,
            'vale' => $datos['vale'] ?? null,
            'id_tipo_agregado' => $datos['id_tipo_agregado'] ?? null,
            'nombre' => $datos['nombre'] ?? null,
            'cantidad' => $datos['cantidad'] ?? 0,
            'codigo_pieza' => $datos['codigo_pieza'] ?? null,
            'motivo' => $datos['motivo'] ?? null,
            'id_motor' => $idMotor,
            'id_entidad' => $ot->id_entidad,
        ]);
    }

    /**
     * Registra un movimiento en taller (nave/valla).
     */
    public function agregarMovimiento(OrdenesTaller $ot, array $datos): MovimientosTaller
    {
        $tiempo = $this->calcularTiempo($datos['fecha_inicio'] ?? null, $datos['hora_inicio'] ?? null, $datos['fecha_final'] ?? null, $datos['hora_final'] ?? null);

        return $ot->movimientos()->create([
            'id_nave' => $datos['id_nave'] ?? null,
            'id_valla' => $datos['id_valla'] ?? null,
            'fecha_inicio' => $datos['fecha_inicio'] ?? null,
            'hora_inicio' => $datos['hora_inicio'] ?? null,
            'fecha_final' => $datos['fecha_final'] ?? null,
            'hora_final' => $datos['hora_final'] ?? null,
            'tiempo' => $tiempo,
            'observaciones' => $datos['observaciones'] ?? null,
            'id_entidad' => $ot->id_entidad,
        ]);
    }

    /**
     * Aplica el estado del tractivo al abrir (EN TALLER/PARALIZADO) o cerrar (ACTIVO) la OT.
     */
    private function aplicarEstadoTractivo(OrdenesTaller $ot, bool $abriendo): void
    {
        $tractivo = Tractivo::find($ot->id_tractivo);
        if (! $tractivo) {
            return;
        }

        if ($abriendo) {
            $tractivo->update([
                'id_tipo_estado' => strtoupper((string) $ot->ot_largo_plazo) === 'SI' ? 25 : 26, // PARALIZADO / EN TALLER
            ]);
        } else {
            $tractivo->update(['id_tipo_estado' => 14]); // ACTIVO
        }
    }

    /**
     * Calcula el tiempo (horas.minutos) entre dos fechas/horas.
     */
    private function calcularTiempo(?string $fi, ?string $hi, ?string $ff, ?string $hf): float
    {
        if (! $fi || ! $ff) {
            return 0;
        }
        try {
            $inicio = \Carbon\Carbon::parse($fi.($hi ? ' '.$hi : ''));
            $final = \Carbon\Carbon::parse($ff.($hf ? ' '.$hf : ''));
            $minutos = max(0, $final->diffInMinutes($inicio));

            return round($minutos / 60, 2);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function siguienteNumero(int $idEntidad): string
    {
        $anio = now()->year;
        $max = OrdenesTaller::withTrashed()
            ->whereYear('fecha_ingreso', $anio)
            ->max('id') ?? 0;

        return $anio.'-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
