<?php

namespace App\Services;

use App\Models\Bolsa;
use App\Models\MovimientoRrhh;
use App\Models\Incidencia;
use App\Models\Penalizacion;
use App\Models\SalarioAdministrativo;
use App\Models\Turno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SalarioAdminCalcService
{
    private float $varcla90 = 0.60;
    private float $varcla91 = 1.15;
    private float $varnoct1 = 0.60;
    private float $varnoct2 = 1.15;
    private float $varmaestria = 440;
    private int $horasMes = 240;

    public function __construct()
    {
        $this->configurarPorEntidad();
    }

    private function configurarPorEntidad(): void
    {
        $entidadId = (int) entidadActivaId();
        if (!$entidadId) return;

        $entidad = \App\Models\Entidad::find($entidadId);
        if ($entidad && ($entidad->id_provincia ?? 0) == 3200) {
            $this->varcla90 = 0.98;
            $this->varcla91 = 1.88;
            $this->varnoct1 = 0.98;
            $this->varnoct2 = 1.88;
            $this->varmaestria = 718.22;
        }
    }

    public function calcularPorMes(int $mes, int $ano): array
    {
        $empleados = $this->obtenerEmpleadosActivos();
        $resultados = [];

        foreach ($empleados as $empleado) {
            $calculado = $this->calcularSalarioEmpleado($empleado, $mes, $ano);
            if ($calculado) {
                $resultados[] = $calculado;
            }
        }

        return $resultados;
    }

    public function calcularSalarioEmpleado(Bolsa $empleado, int $mes, int $ano): ?array
    {
        $movimiento = MovimientoRrhh::where('id_bolsa', $empleado->id)
            ->whereNull('fbaja')
            ->first();

        if (!$movimiento) return null;

        $cargo = $empleado->cargo;
        $tarifa = (float) ($cargo?->tarifa ?? 0);
        $cla = (float) ($cargo?->cla ?? 0);

        $salarioAdmin = SalarioAdministrativo::where('id_movimiento', $movimiento->id)
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $ano)
            ->first();

        $irregular = (float) ($salarioAdmin?->irregular ?? 0);
        $feriadosEditados = (float) ($salarioAdmin?->feriados ?? 0);
        $diasTaller = (float) ($salarioAdmin?->dias_taller ?? 0);
        $hExtra = (float) ($salarioAdmin?->h_extra ?? 0);
        $impHExtra = (float) ($salarioAdmin?->imp_h_extra ?? 0);

        $tiempoMes = $this->calcularTiempoMes($mes, $ano);
        $regular = $tiempoMes;

        $incidencias = $this->obtenerIncidencias($empleado->id, $mes, $ano);
        $turnos = $this->obtenerTurnos($movimiento->id, $mes, $ano);

        $noct1 = 0;
        $noct2 = 0;
        $tdoblaje = 0;
        $idGrupoHorario = $cargo?->id_grupo_horario ?? 254;
        $esTurnos = $idGrupoHorario === 256;

        if ($turnos->isNotEmpty()) {
            $noct1 = (float) $turnos->sum('noct1');
            $noct2 = (float) $turnos->sum('noct2');
            $tdoblaje = (float) $turnos->sum('doblaje');
            $regular = (float) $turnos->sum('tiempo');
        } elseif (!$esTurnos) {
            $regular = $tiempoMes;
        } else {
            $regular = 0;
        }

        $tadrl = 0;
        $impIncidencia = 0;
        $tIncidencias = 0;
        $vacaciones = 0;
        $reubicado = 0;
        $recalificacion = 0;
        $tgarantia = 0;
        $impGarantia = 0;
        $impFeriados = 0;
        $impFeriadosT = 0;
        $preceso = 0;
        $impreceso = 0;

        foreach ($incidencias as $inc) {
            $clave = $inc->tipoIncidencia?->origen_id ?? $inc->id_tipo_incidencia;
            $tiempo = (float) $inc->periodo_actual;
            $importe = (float) $inc->importe;

            match ((string) $clave) {
                '52' => $tadrl += $tiempo,
                '6' => $vacaciones += $tiempo,
                '43' => $reubicado += $tiempo,
                '48' => $recalificacion += $tiempo,
                '3', '44' => $tgarantia += $tiempo,
                '46' => $tgarantia += $tiempo,
                default => $tIncidencias += $tiempo,
            };

            if (in_array((string) $clave, ['3', '44', '46'])) {
                $impGarantia += $importe;
            }

            if ((string) $clave === '15') {
                $impFeriados -= $importe;
            }

            if ((string) $clave === '25') {
                $impFeriadosT += $importe;
            }

            $impIncidencia += $importe;
        }

        $regular += $tadrl;
        $ttotal = $regular + $irregular + $diasTaller;

        if ($ttotal > $this->horasMes) {
            $ttotal = $this->horasMes;
        }

        $impRegular = round($regular * $tarifa, 2);
        $impIrregular = round($irregular * $tarifa, 2);
        $impCla = round($ttotal * $cla, 2);
        $impDoblaje = round($tdoblaje * $tarifa * 1.25, 2);

        $impNoct1 = round($noct1 * $this->varnoct1, 2);
        $impNoct2 = round($noct2 * $this->varnoct2, 2);
        $impNocturnidad = $impNoct1 + $impNoct2;

        $tara = 0;
        $impMaestrias = 0;
        if (($empleado->nivel_educacional ?? '') === 'MAESTRIA') {
            $tara = $this->varmaestria / max($regular, 1);
            $impMaestrias = min($ttotal * $tara, $this->varmaestria);
        }

        $impBase = $impRegular + $impIrregular + $impCla;

        $tarExtra = $tarifa + ($tarifa * 0.25);
        $tiempoExtra = $hExtra + $tdoblaje;
        $impExtra = round($tarExtra * $tiempoExtra, 2);

        $padicionales = $impNocturnidad + $impMaestrias + $impDoblaje + $impHExtra + $impIncidencia + $impFeriados + $impFeriadosT + $impreceso;

        $st = $impBase + $padicionales;
        $impSalFinal = $st;

        $normaSalarial = $impBase > 0 ? round($impSalFinal / $impBase, 4) : 0;
        $normaTransp = $ttotal > 0 ? round($impSalFinal / $ttotal, 4) : 0;

        return [
            'id_bolsa' => $empleado->id,
            'id_movimiento' => $movimiento->id,
            'id_salario_admin' => $salarioAdmin?->id,
            'nombre_completo' => $empleado->nombrecompleto,
            'cargo' => $cargo?->nombre ?? '',
            'area' => $empleado->area?->nombre ?? '',
            'tarifa' => $tarifa,
            'cla' => $cla,
            'id_grupo_horario' => $idGrupoHorario,
            'es_turnos' => $esTurnos,
            'turnos' => $turnos->map(fn ($t) => [
                'id' => $t->id,
                'inicio' => $t->inicio?->format('Y-m-d'),
                'final' => $t->final?->format('Y-m-d'),
                'tiempo' => (float) $t->tiempo,
                'noct1' => (float) $t->noct1,
                'noct2' => (float) $t->noct2,
                'doblaje' => (float) $t->doblaje,
            ])->values(),
            'regular' => round($regular, 2),
            'irregular' => round($irregular, 2),
            'feriados_editados' => round($feriadosEditados, 2),
            'ttotal' => round($ttotal, 2),
            'tiempo_mes' => $tiempoMes,
            'noct1' => round($noct1, 2),
            'noct2' => round($noct2, 2),
            'tdoblaje' => round($tdoblaje, 2),
            'h_extra' => round($hExtra, 2),
            'dias_taller' => round($diasTaller, 2),
            'imp_regular' => $impRegular,
            'imp_irregular' => $impIrregular,
            'imp_cla' => $impCla,
            'imp_doblaje' => $impDoblaje,
            'imp_nocturnidad_1' => $impNoct1,
            'imp_nocturnidad_2' => $impNoct2,
            'imp_nocturnidad' => $impNocturnidad,
            'imp_maestrias' => round($impMaestrias, 2),
            'imp_h_extra' => $impHExtra,
            'imp_extra' => $impExtra,
            'imp_incidencia' => round($impIncidencia, 2),
            'imp_feriados' => round($impFeriados, 2),
            'imp_feriados_t' => round($impFeriadosT, 2),
            'imp_garantia' => round($impGarantia, 2),
            'imp_base' => $impBase,
            'padicionales' => round($padicionales, 2),
            'salario_final' => round($impSalFinal, 2),
            'norma_salarial' => $normaSalarial,
            'norma_transp' => $normaTransp,
            'vacaciones' => round($vacaciones, 2),
            'incidencias' => $incidencias,
            'tiempo_mes_label' => $this->obtenerLabelTiempoMes($mes, $ano),
        ];
    }

    public function calcularTiempoMes(int $mes, int $ano): float
    {
        $dias = (int) date('t', mktime(0, 0, 0, $mes, 1, $ano));
        $domingos = 0;
        $sabados = 0;
        $viernes = 0;

        for ($i = 1; $i <= $dias; $i++) {
            $diaSemana = (int) date('w', mktime(0, 0, 0, $mes, $i, $ano));
            if ($diaSemana == 0) $domingos++;
            if ($diaSemana == 6) $sabados++;
            if ($diaSemana == 5) $viernes++;
        }

        $resto = round($dias - ($domingos + $sabados + $viernes), 2);

        $entidadId = (int) entidadActivaId();
        if ($entidadId) {
            $entidad = \App\Models\Entidad::find($entidadId);
            if ($entidad && ($entidad->id_provincia ?? 0) == 3200) {
                return round((($resto + $viernes) * 8) + ($sabados * 4), 2);
            }
        }

        return round(($resto * 9) + ($viernes * 8), 2);
    }

    private function obtenerLabelTiempoMes(int $mes, int $ano): string
    {
        $dias = (int) date('t', mktime(0, 0, 0, $mes, 1, $ano));
        $domingos = 0;
        $sabados = 0;
        $viernes = 0;

        for ($i = 1; $i <= $dias; $i++) {
            $diaSemana = (int) date('w', mktime(0, 0, 0, $mes, $i, $ano));
            if ($diaSemana == 0) $domingos++;
            if ($diaSemana == 6) $sabados++;
            if ($diaSemana == 5) $viernes++;
        }

        $resto = $dias - ($domingos + $sabados + $viernes);
        return "{$resto} días Lun-Jue, {$viernes} Viernes, {$sabados} Sábados, {$domingos} Domingos";
    }

    private function obtenerEmpleadosActivos()
    {
        return Bolsa::where('activo', true)
            ->whereHas('movimientosRrhh', function ($q) {
                $q->whereNull('fbaja');
            })
            ->with(['cargo:id,nombre,tarifa,cla', 'area:id,nombre', 'movimientosRrhh' => function ($q) {
                $q->whereNull('fbaja');
            }])
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get();
    }

    private function obtenerIncidencias(int $idBolsa, int $mes, int $ano)
    {
        return Incidencia::where('id_bolsa', $idBolsa)
            ->whereYear('fecha_inicio', $ano)
            ->whereMonth('fecha_inicio', $mes)
            ->with('tipoIncidencia')
            ->get();
    }

    private function obtenerTurnos(?int $idMovimiento, int $mes, int $ano)
    {
        if (!$idMovimiento) return collect();

        return Turno::where('idmovimientos', $idMovimiento)
            ->whereMonth('inicio', $mes)
            ->whereYear('inicio', $ano)
            ->get();
    }
}
