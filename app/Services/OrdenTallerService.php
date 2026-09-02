<?php

namespace App\Services;

use App\Models\CatalogoItem;
use App\Models\GastosOrden;
use App\Models\MovimientosTaller;
use App\Models\OrdenesOperacione;
use App\Models\OrdenesTaller;
use App\Models\TiposMantenimiento;
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

        // Cálculo de mantenimiento programado (réplica del legacy ModTaller).
        // Si el motivo es ciclo de mantenimiento, el tipo se resuelve de la
        // ficha del vehículo (tipo_vehiculos.id_tipo_mantenimiento) y el plan
        // completo se calcula automáticamente.
        $tractivo = Tractivo::find($datos['id_tractivo']);
        if ($this->esMantenimientoProgramado($datos['id_motivo_entrada'] ?? null) && $tractivo) {
            $plan = $this->calcularPlanTractivo($tractivo, $datos['kilometraje'] ?? null);
            if ($plan['id_tipo_mantenimiento']) {
                $datos['id_tipo_mantenimiento'] = $plan['id_tipo_mantenimiento'];
            }
        } else {
            $plan = $this->calcularPlanMantenimiento(
                $datos['id_motivo_entrada'] ?? null,
                $datos['id_tipo_mantenimiento'] ?? null,
                $datos['kilometraje'] ?? null
            );
        }

        return DB::transaction(function () use ($datos, $idEntidad, $plan) {
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
                'ot_paralizado' => $datos['ot_paralizado'] ?? null,
                'ot_rotura_en_linea' => $datos['ot_rotura_en_linea'] ?? null,
                'combtaller' => $datos['combtaller'] ?? 0,
                'id_motor' => $datos['id_motor'] ?? null,
                'id_taller' => $datos['id_taller'] ?? null,
                'id_entidad' => $idEntidad,
                'id_unidad' => $idEntidad,
                'estado' => 'abierta',
                'kilometraje' => $datos['kilometraje'] ?? null,
                'tipo_mtto' => $plan['tipo_mtto'],
                'km_mtto' => $plan['km_mtto'],
                'planificacion' => $plan['planificacion'],
                'km_mtto_prox' => $plan['km_mtto_prox'],
                'pl_cil1' => $datos['pl_cil1'] ?? null,
                'pl_cil2' => $datos['pl_cil2'] ?? null,
                'pl_cil3' => $datos['pl_cil3'] ?? null,
                'pl_cil4' => $datos['pl_cil4'] ?? null,
                'pl_cil5' => $datos['pl_cil5'] ?? null,
                'pl_cil6' => $datos['pl_cil6'] ?? null,
                'pl_cil7' => $datos['pl_cil7'] ?? null,
                'pl_cil8' => $datos['pl_cil8'] ?? null,
                'pl_cons_comb' => $datos['pl_cons_comb'] ?? null,
                'pl_cons_aceite' => $datos['pl_cons_aceite'] ?? null,
                'pl_presion_aceite_baja' => $datos['pl_presion_aceite_baja'] ?? null,
                'pl_presion_aceite_alta' => $datos['pl_presion_aceite_alta'] ?? null,
                'pl_temp_agua' => $datos['pl_temp_agua'] ?? null,
                'pl_temp_aceite' => $datos['pl_temp_aceite'] ?? null,
                'pl_observacion' => $datos['pl_observacion'] ?? null,
            ]);

            // Filas del formulario único (operaciones / piezas / movimientos).
            foreach ($datos['operaciones'] ?? [] as $op) {
                $this->agregarOperacion($ot, $op);
            }
            foreach ($datos['gastos'] ?? [] as $g) {
                $this->agregarGasto($ot, $g);
            }
            foreach ($datos['movimientos'] ?? [] as $m) {
                $this->agregarMovimiento($ot, $m);
            }

            $this->aplicarEstadoTractivo($ot, true);

            return $ot;
        });
    }

    /**
     * Reemplaza las filas (operaciones / gastos / movimientos) de una OT con las
     * del formulario. Se usa tanto en alta como en edición (semántica replace).
     */
    public function reemplazarFilas(OrdenesTaller $ot, array $datos): void
    {
        $ot->operaciones()->delete();
        $ot->gastos()->delete();
        $ot->movimientos()->delete();

        foreach ($datos['operaciones'] ?? [] as $op) {
            $this->agregarOperacion($ot, $op);
        }
        foreach ($datos['gastos'] ?? [] as $g) {
            $this->agregarGasto($ot, $g);
        }
        foreach ($datos['movimientos'] ?? [] as $m) {
            $this->agregarMovimiento($ot, $m);
        }
    }

    /**
     * Calcula el plan de mantenimiento programado (fórmula legacy de ModTaller::
     * mostrar_ciclo_mtto / actualizar_submit). Solo aplica cuando el motivo de
     * entrada es "CICLO DE MANTENIMIENTOS" (origen_id 6 del catálogo unificado).
     *
     * kmmtto      = round(km / frecuencia) * frecuencia
     * planificacion = km + frecuencia
     * kmmttoprox  = round(planificacion / frecuencia) * frecuencia
     */
    public function calcularPlanMantenimiento(?int $idMotivoEntrada, ?int $idTipoMantenimiento, $kilometraje): array
    {
        $vacio = ['tipo_mtto' => null, 'km_mtto' => null, 'planificacion' => null, 'km_mtto_prox' => null];

        if (! $this->esMantenimientoProgramado($idMotivoEntrada)) {
            return $vacio;
        }

        $tm = $idTipoMantenimiento ? TiposMantenimiento::find($idTipoMantenimiento) : null;
        if (! $tm || empty($tm->frecuencia) || ! is_numeric($kilometraje)) {
            return $vacio;
        }

        $frec = (float) $tm->frecuencia;
        $km = (float) $kilometraje;

        $kmmtto = round($km / $frec) * $frec;
        $planificacion = $km + $frec;
        $kmMttoProx = round($planificacion / $frec) * $frec;

        return [
            'tipo_mtto' => $tm->nombre,
            'km_mtto' => $kmmtto,
            'planificacion' => $planificacion,
            'km_mtto_prox' => $kmMttoProx,
        ];
    }

    /**
     * Calcula el plan de mantenimiento de un tractivo (réplica del legacy
     * ModTaller::mostrar_ciclo_mtto + Taller::actualizar_submit).
     *
     * - El ciclo (tipo de mantenimiento) sale de la ficha del vehículo:
     *   tractivo.id_tipo_vehiculo → tipo_vehiculos.id_tipo_mantenimiento.
     * - km_mtto = round(km / frecuencia) * frecuencia; el tipo de mtto que le
     *   corresponde ('Rev' o el ciclo: 5000, 15000, ...) es la descripción de
     *   la línea del plan en ese kilometraje (lineas_mantenimiento).
     * - planificación:
     *   - con línea 'Rev': si la entidad planifica por tipo 0 → última
     *     planificación de OT de ciclo del tractivo (o kms_plan_mtto) +
     *     frecuencia; si no → km + frecuencia.
     *   - con línea distinta de 'Rev': km + frecuencia.
     *   - sin línea en el plan: frecuencia.
     * - km_mtto_prox = round(planificación / frecuencia) * frecuencia.
     *
     * @return array{id_tipo_mantenimiento:?int, tipo_mtto:?string, tipo_mtto_label:?string, km_mtto:?float, planificacion:?float, km_mtto_prox:?float}
     */
    public function calcularPlanTractivo(Tractivo $tractivo, $kilometraje): array
    {
        $vacio = [
            'id_tipo_mantenimiento' => null,
            'tipo_mtto' => null,
            'tipo_mtto_label' => null,
            'km_mtto' => null,
            'planificacion' => null,
            'km_mtto_prox' => null,
        ];

        $idTipoMtto = $tractivo->tipoVehiculo?->id_tipo_mantenimiento;
        $tm = $idTipoMtto ? TiposMantenimiento::find($idTipoMtto) : null;
        $frec = (float) ($tm?->frecuencia ?? 0);

        if (! $tm || $frec <= 0 || ! is_numeric($kilometraje)) {
            return $vacio + ['id_tipo_mantenimiento' => $idTipoMtto];
        }

        $km = (float) $kilometraje;
        $kmMtto = round($km / $frec) * $frec;

        // Línea del plan en ese kilometraje (legacy mostrar_ciclo_mtto).
        $tipoLinea = \App\Models\LineasMantenimiento::where('id_tipo_mantenimiento', $tm->id)
            ->where('kilometraje', $kmMtto)
            ->value('descripcion');
        $tipoLinea = $tipoLinea !== null ? trim((string) $tipoLinea) : null;
        $tipoLinea = $tipoLinea === '' ? null : $tipoLinea;

        // Planificación (legacy Taller::actualizar_submit).
        if ($tipoLinea !== null) {
            if (strcasecmp($tipoLinea, 'Rev') === 0 && (int) ($tractivo->entidad?->tipo_planificacion ?? 0) === 0) {
                $base = $this->ultimaPlanificacionCiclo($tractivo) ?? (float) ($tractivo->kms_plan_mtto ?? 0);
                $planificacion = $base + $frec;
            } else {
                $planificacion = $km + $frec;
            }
        } else {
            $planificacion = $frec;
        }

        return [
            'id_tipo_mantenimiento' => $tm->id,
            'tipo_mtto' => $tipoLinea,
            'tipo_mtto_label' => $tipoLinea === null
                ? null
                : (strcasecmp($tipoLinea, 'Rev') === 0 ? 'REVISION MECANICA' : 'MTTO '.$tipoLinea),
            'km_mtto' => $kmMtto,
            'planificacion' => $planificacion,
            'km_mtto_prox' => round($planificacion / $frec) * $frec,
        ];
    }

    /**
     * Última planificación registrada en una OT de ciclo de mantenimiento del
     * tractivo (legacy mostrar_ordentaller_tractivo_mtto 'ULTIMA': motivo
     * origen_id=6, entrada anterior o igual a la fecha de operaciones).
     */
    private function ultimaPlanificacionCiclo(Tractivo $tractivo): ?float
    {
        $motivoCicloId = \App\Support\Catalogos::idDe('motivos_entrada_taller', 6);
        if (! $motivoCicloId) {
            return null;
        }

        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();

        $valor = OrdenesTaller::where('id_tractivo', $tractivo->id)
            ->where('id_motivo_entrada', $motivoCicloId)
            ->whereDate('fecha_ingreso', '<=', $fechaOperaciones)
            ->orderByDesc('fecha_ingreso')
            ->value('planificacion');

        return $valor !== null ? (float) $valor : null;
    }

    /**
     * Determina si el motivo de entrada corresponde a mantenimiento programado.
     * El legacy marca idmotentrada = 6 (CICLO DE MANTENIMIENTOS).
     */
    public function esMantenimientoProgramado(?int $idMotivoEntrada): bool
    {
        if (! $idMotivoEntrada) {
            return false;
        }

        return (bool) CatalogoItem::where('id', $idMotivoEntrada)
            ->where('tipo', 'motivos_entrada_taller')
            ->where('origen_id', 6)
            ->exists();
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
     * Actualiza una operación y recalcula su tiempo y el ottiempo de la OT.
     */
    public function actualizarOperacion(OrdenesOperacione $op, array $datos): OrdenesOperacione
    {
        $ot = $op->orden;
        $anterior = (float) $op->tiempo;

        $tiempo = $this->calcularTiempo($datos['fecha_inicio'] ?? null, $datos['hora_inicio'] ?? null, $datos['fecha_final'] ?? null, $datos['hora_final'] ?? null);

        $op->update([
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
        ]);

        if ($ot) {
            $ot->update(['ottiempo' => max(0, (float) $ot->ottiempo - $anterior + $tiempo)]);
        }

        return $op;
    }

    /**
     * Elimina una operación y descuenta su tiempo del ottiempo de la OT.
     */
    public function eliminarOperacion(OrdenesOperacione $op): void
    {
        $ot = $op->orden;
        $anterior = (float) $op->tiempo;
        $op->delete();

        if ($ot) {
            $ot->update(['ottiempo' => max(0, (float) $ot->ottiempo - $anterior)]);
        }
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
     * Actualiza una pieza/recurso de almacén de la OT.
     */
    public function actualizarGasto(GastosOrden $gasto, array $datos): GastosOrden
    {
        $idMotor = $datos['id_motor'] ?? $gasto->id_motor;

        $gasto->update([
            'importe_me' => $datos['importe_me'] ?? 0,
            'vale' => $datos['vale'] ?? null,
            'id_tipo_agregado' => $datos['id_tipo_agregado'] ?? null,
            'nombre' => $datos['nombre'] ?? null,
            'cantidad' => $datos['cantidad'] ?? 0,
            'codigo_pieza' => $datos['codigo_pieza'] ?? null,
            'motivo' => $datos['motivo'] ?? null,
            'id_motor' => $idMotor,
        ]);

        return $gasto;
    }

    /**
     * Elimina una pieza/recurso de almacén de la OT.
     */
    public function eliminarGasto(GastosOrden $gasto): void
    {
        $gasto->delete();
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
     * Actualiza un movimiento en taller (nave/valla).
     */
    public function actualizarMovimiento(MovimientosTaller $movimiento, array $datos): MovimientosTaller
    {
        $tiempo = $this->calcularTiempo($datos['fecha_inicio'] ?? null, $datos['hora_inicio'] ?? null, $datos['fecha_final'] ?? null, $datos['hora_final'] ?? null);

        $movimiento->update([
            'id_nave' => $datos['id_nave'] ?? null,
            'id_valla' => $datos['id_valla'] ?? null,
            'fecha_inicio' => $datos['fecha_inicio'] ?? null,
            'hora_inicio' => $datos['hora_inicio'] ?? null,
            'fecha_final' => $datos['fecha_final'] ?? null,
            'hora_final' => $datos['hora_final'] ?? null,
            'tiempo' => $tiempo,
            'observaciones' => $datos['observaciones'] ?? null,
        ]);

        return $movimiento;
    }

    /**
     * Elimina un movimiento en taller.
     */
    public function eliminarMovimiento(MovimientosTaller $movimiento): void
    {
        $movimiento->delete();
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
            $minutos = abs($final->diffInMinutes($inicio));

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
