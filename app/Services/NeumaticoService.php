<?php

namespace App\Services;

use App\Models\Neumatico;
use App\Models\NeumaticosMovimiento;

/**
 * Lógica de negocio de neumáticos (réplica del legacy CI3 ModNeumaticos).
 *
 * - Movimientos de montaje/desmontaje: cada cambio de vehículo/posición/destino
 *   crea un movimiento nuevo y cierra el anterior (km_retirado/fecha_retiro).
 * - Retiro con motivo: requiere tipo + código de rotura (se guarda en cabecera).
 * - Plan predictivo: calcula fplanretirado/fplanaviso/kmspromedio desde la vida
 *   útil de la entidad (vidaneumnuevo/rec/admin) y los km reales recorridos.
 */
class NeumaticoService
{
    // Destinos (tec_destagregados)
    public const DESTINO_VEHICULO = 1;
    public const POSICION_REPUESTO = 5;

    /**
     * Registra un movimiento de montaje/desmontaje de un neumático.
     * Cierra el movimiento vigente (si hay) y abre uno nuevo.
     */
    public function registrarMovimiento(
        Neumatico $neumatico,
        int $idTractivo,
        ?string $fechaMontaje,
        ?float $kmInstalado,
        ?int $idPosicion,
        ?int $idDestino = self::DESTINO_VEHICULO,
        ?string $observaciones = null
    ): NeumaticosMovimiento {
        $fechaMontaje = $fechaMontaje ?? now()->toDateString();
        $idPosicion = $idPosicion ?? self::POSICION_REPUESTO;
        $kmInstalado = (int) $idPosicion === self::POSICION_REPUESTO ? 0 : ($kmInstalado ?? 0);

        // Cerrar el movimiento vigente (fretirada IS NULL equivalente)
        $vigente = $neumatico->movimientos()->whereNull('fecha_retiro')->orderByDesc('id')->first();
        if ($vigente) {
            $vigente->update([
                'fecha_retiro' => $fechaMontaje,
                'km_retirado' => (int) $idPosicion === self::POSICION_REPUESTO ? 0 : ($vigente->km_retirado ?? $kmInstalado),
            ]);
        }

        $movimiento = $neumatico->movimientos()->create([
            'id_tractivo' => $idTractivo ?: null,
            'fecha_montaje' => $fechaMontaje,
            'km_instalado' => $kmInstalado,
            'posicion' => $idPosicion,
            'id_destino' => $idDestino,
            'observaciones' => $observaciones,
            'id_entidad' => $neumatico->id_entidad,
        ]);

        // Actualizar cabecera
        $neumatico->update([
            'id_tractivo' => (int) $idDestino === self::DESTINO_VEHICULO ? $idTractivo : null,
            'id_posicion' => $idPosicion,
            'fecha_instalacion' => $movimiento->fecha_montaje,
            'kilometraje' => $kmInstalado,
        ]);

        $this->calcularPlan($neumatico);

        return $movimiento;
    }

    /**
     * Da de baja un neumático con motivo de rotura obligatorio.
     */
    public function retirar(
        Neumatico $neumatico,
        ?string $fechaRetiro,
        ?float $kmRetirado,
        ?int $idTipoRotura,
        ?int $idRotura
    ): void {
        $fechaRetiro = $fechaRetiro ?? now()->toDateString();

        $neumatico->update([
            'fecha_retiro' => $fechaRetiro,
            'estado' => 'baja',
        ]);

        // Cerrar movimiento vigente
        $vigente = $neumatico->movimientos()->whereNull('fecha_retiro')->orderByDesc('id')->first();
        if ($vigente) {
            $vigente->update([
                'fecha_retiro' => $fechaRetiro,
                'km_retirado' => $kmRetirado,
            ]);
        }

        $this->calcularPlan($neumatico);
    }

    /**
     * Recalcula el plan predictivo de bajas de un neumático activo:
     * km promedio mensual + fechas planificadas de retiro y aviso.
     *
     * Vida útil según tipo (legacy calcular_plan): grupo 7 (ADMINISTRATIVO)
     * usa vidaneumadmin; estados de recauchado/regrabe usan vidaneumrec; el resto
     * vidaneumnuevo. Si la entidad no define vida útil, se omite.
     */
    public function calcularPlan(Neumatico $neumatico): void
    {
        if ($neumatico->fecha_retiro) {
            return;
        }

        $entidadId = $neumatico->id_entidad;
        $vida = ['nuevo' => 0, 'rec' => 0, 'admin' => 0];
        try {
            $rh = \Illuminate\Support\Facades\DB::connection('legacy')
                ->table('rh_entidades')->where('identidades', $entidadId)->first();
            if ($rh) {
                $vida = [
                    'nuevo' => (int) ($rh->vidaneumnuevo ?? 0),
                    'rec' => (int) ($rh->vidaneumrec ?? 0),
                    'admin' => (int) ($rh->vidaneumadmin ?? 0),
                ];
            }
        } catch (\Throwable) {
            $vida = ['nuevo' => 0, 'rec' => 0, 'admin' => 0];
        }
        $vidanuevo = $vida['nuevo'];
        $vidarec = $vida['rec'];
        $vidaadmin = $vida['admin'];
        if (! $vidanuevo && ! $vidarec && ! $vidaadmin) {
            return;
        }

        $kmRecorridos = (float) $this->kmsRecorridos($neumatico);
        $esAdministrativo = (int) ($neumatico->tractivo?->id_grupo ?? 0) === 7;
        $estado = strtolower((string) $neumatico->estado);
        $esRecauchado = in_array($estado, ['recauchado', 'regrabado', 'trabajando'], true);

        $plan = $esAdministrativo ? $vidaadmin : ($esRecauchado ? $vidarec : $vidanuevo);
        if (! $plan) {
            return;
        }

        $fInstalacion = $neumatico->fecha_instalacion ? \Carbon\Carbon::parse($neumatico->fecha_instalacion) : now();
        $dias = max(1, now()->diffInDays($fInstalacion));
        $kmsPromedio = $kmRecorridos > 0 ? $kmRecorridos / max(1, $dias / 30) : 0;

        $mesesRestantes = $kmsPromedio > 0 ? ($plan - $kmRecorridos) / $kmsPromedio : null;

        $neumatico->update([
            'kms_promedio' => round($kmsPromedio, 2),
            'fecha_plan_retiro' => $mesesRestantes !== null ? now()->addMonths((int) round($mesesRestantes)) : null,
            'fecha_plan_aviso' => $mesesRestantes !== null ? now()->addMonths(max(0, (int) round($mesesRestantes) - 1)) : null,
        ]);
    }

    /**
     * Kilómetros recorridos por un neumático = suma de (km_retirado - km_instalado)
     * de sus movimientos cerrados + el tramo abierto si está en vehículo.
     */
    public function kmsRecorridos(Neumatico $neumatico): float
    {
        $total = 0.0;
        foreach ($neumatico->movimientos as $mov) {
            if ($mov->fecha_retiro) {
                $total += (float) ($mov->km_retirado ?? 0) - (float) ($mov->km_instalado ?? 0);
            }
        }
        $vigente = $neumatico->movimientos()->whereNull('fecha_retiro')->first();
        if ($vigente && (int) $vigente->id_destino === self::DESTINO_VEHICULO && $neumatico->tractivo) {
            $total += (float) ($neumatico->tractivo->kilometraje_actual ?? 0) - (float) ($vigente->km_instalado ?? 0);
        }

        return max(0, $total);
    }
}
