<?php

namespace App\Services;

use App\Models\Aforo;
use App\Models\Bolsa;
use App\Models\Incidencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SalarioChoferCalcService
{
    private float $varcla90 = 1.20;
    private float $varcla91 = 2.30;
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

    public function calcularPorChofer(int $mes, int $ano, ?int $idBolsa = null): array
    {
        $choferes = $this->obtenerChoferesActivos();
        $resultados = [];

        foreach ($choferes as $chofer) {
            $calculado = $this->calcularSalarioChofer($chofer->id, $mes, $ano);
            if ($calculado) {
                $resultados[] = $calculado;
            }
        }

        if ($idBolsa) {
            $resultados = array_filter($resultados, fn($r) => $r['id_bolsa'] == $idBolsa);
        }

        return array_values($resultados);
    }

    public function calcularSalarioChofer(int $idBolsa, int $mes, int $ano): ?array
    {
        $aforos = $this->obtenerAforosChofer($idBolsa, $mes, $ano);
        if ($aforos->isEmpty()) return null;

        $chofer = Bolsa::find($idBolsa);
        if (!$chofer) return null;

        $cargo = $chofer->cargo;
        $tarifa = $cargo->tarifa ?? 0;

        $feriados = $this->obtenerFeriadosMes($mes, $ano);
        $incidencias = $this->obtenerIncidencias($idBolsa, $mes, $ano);

        $totalSalario = 0;
        $totalIngresos = 0;
        $totalCla = 0;
        $totalFeriados = 0;
        $totalNoct1 = 0;
        $totalNoct2 = 0;
        $totalTnReal = 0;
        $totalKmTotal = 0;
        $totalTiempo = 0;
        $totalAlmacenaje = 0;
        $detalle = [];

        foreach ($aforos as $aforo) {
            $cp = $aforo->cartaPorte;
            if (!$cp || $cp->cancelada) continue;

            $hr = $cp->hojaRuta;
            if (!$hr || $hr->cancelada) continue;

            $ingresoMt = (float) $aforo->ingreso_mt;
            $tasa = (float) $aforo->tasa;
            $almFlete = (float) ($aforo->almacenaje_flete ?? 0);
            $kmTotal = (float) $aforo->km_total_total;
            $kmCarga = (float) $aforo->km_carga_total;
            $tTotal = (float) $aforo->tiempo_total;
            $tnReal = (float) $aforo->tn_real_total;
            $noct1 = (float) $aforo->recargo_1;
            $noct2 = (float) $aforo->recargo_2;
            $tFeriado = (float) $aforo->tiempo_feriado;

            $chofer2Id = $hr->id_chofer2;
            $esDobleChofer = $chofer2Id && $chofer2Id != $idBolsa;

            $ingreso = $ingresoMt;
            $salalm = 0;

            if ($esDobleChofer) {
                if ($kmCarga <= 250) {
                    $tnReal = round($tnReal / 2, 2);
                }
                if ($almFlete > 0) {
                    $ingreso = round($ingresoMt - $almFlete, 2);
                    $salalm = round(($almFlete / 2) * 0.005, 2);
                } else {
                    $ingreso = round($ingresoMt / 2, 2);
                }
                $tasa2 = (float) ($aforo->tasa->tasa2 ?? 0);
                $salario = $tasa2 > 0
                    ? round($ingreso * $tasa2 + $salalm, 2)
                    : round($ingreso * $tasa + $salalm, 2);
            } else {
                if ($almFlete > 0) {
                    $ingreso = round($ingresoMt - $almFlete, 2);
                    $salalm = round($almFlete * 0.005, 2);
                }
                $salario = round($ingreso * $tasa + $salalm, 2);
            }

            $impCla = $kmCarga <= 90
                ? round($tTotal * $this->varcla90, 2)
                : round($tTotal * $this->varcla91, 2);

            $esFeriado = false;
            if (!empty($feriados) && $feriados[0] !== '') {
                $fcarga = $aforo->fecha_carga;
                $fdescarga = $aforo->fecha_descarga;
                if ($fcarga && $fdescarga) {
                    $diaCarga = (int) $fcarga->format('j');
                    $diaDescarga = (int) $fdescarga->format('j');
                    foreach ($feriados as $f) {
                        $diaF = (int) $f;
                        if ($diaF >= $diaCarga && $diaF <= $diaDescarga) {
                            $esFeriado = true;
                            break;
                        }
                    }
                }
            }

            $totalSalario += $salario;
            $totalIngresos += $ingreso;
            $totalCla += $impCla;
            $totalNoct1 += $noct1;
            $totalNoct2 += $noct2;
            $totalTnReal += $tnReal;
            $totalKmTotal += $kmTotal;
            $totalTiempo += $tTotal;
            $totalAlmacenaje += $almFlete;

            if ($esFeriado && $tFeriado == 0) {
                $totalFeriados += $salario;
            }

            $detalle[] = [
                'id_carta_porte' => $cp->id,
                'numero_cp' => $cp->numero,
                'fecha_parte' => $aforo->fecha_parte?->format('d/m/Y'),
                'tractivo' => $hr->tractivo?->cod_tractivo ?? '—',
                'origen' => $cp->solicitud?->lugarOrigen?->nombre ?? '—',
                'destino' => $cp->solicitud?->lugarDestino?->nombre ?? '—',
                'km_total' => round($kmTotal, 2),
                'tiempo_total' => round($tTotal, 2),
                'tn_real' => round($tnReal, 2),
                'ingreso' => round($ingreso, 2),
                'tasa' => $tasa,
                'salario' => round($salario, 2),
                'imp_cla' => round($impCla, 2),
                'noct1' => round($noct1, 2),
                'noct2' => round($noct2, 2),
                'es_feriado' => $esFeriado,
                'doble_chofer' => $esDobleChofer,
            ];
        }

        if ($totalTiempo > $this->horasMes) {
            $totalTiempo = $this->horasMes;
        }

        $regular = $totalTiempo;
        $irregular = 0;
        if ($totalTiempo > $this->horasMes) {
            $regular = $this->horasMes;
            $irregular = round($totalTiempo - $this->horasMes, 2);
        }

        $subsidios = 0;
        $vacaciones = 0;
        $otros = 0;
        foreach ($incidencias as $inc) {
            $clave = $inc->tipoIncidencia?->origen_id ?? $inc->id_tipo_incidencia;
            $tiempo = (float) $inc->periodo_actual;
            match ((string) $clave) {
                '12', '20' => $subsidios += $tiempo,
                '25' => $vacaciones += $tiempo,
                default => $otros += $tiempo,
            };
        }

        $tiempoIncidencias = round($subsidios + $vacaciones + $otros, 2);
        $tTrabajado = round($totalTiempo + $tiempoIncidencias, 2);
        if ($tTrabajado > $this->horasMes) {
            $dif = round($tTrabajado - $this->horasMes, 2);
            $totalTiempo -= $dif;
            $regular = $totalTiempo;
        }

        if ($totalTiempo < 0) $totalTiempo = 0;
        if ($regular < 0) $regular = 0;
        if ($irregular < 0) $irregular = 0;

        $impNoct1 = round($totalNoct1 * $this->varnoct1, 2);
        $impNoct2 = round($totalNoct2 * $this->varnoct2, 2);
        $salarioFinal = round($totalSalario + $totalCla + $totalFeriados + $impNoct1 + $impNoct2, 2);

        return [
            'id_bolsa' => $idBolsa,
            'nombre_completo' => $chofer->nombrecompleto,
            'carnet' => $chofer->ci ?? '',
            'cargo' => $cargo->nombre ?? '',
            'tarifa' => $tarifa,
            'mes' => $mes,
            'ano' => $ano,
            'regular' => round($regular, 2),
            'irregular' => round($irregular, 2),
            't_total' => round($totalTiempo, 2),
            'tiempo_mes' => $this->horasMes,
            'ingresos' => round($totalIngresos, 2),
            'salario_cp' => round($totalSalario, 2),
            'imp_cla' => round($totalCla, 2),
            'imp_nocturnidad_1' => $impNoct1,
            'imp_nocturnidad_2' => $impNoct2,
            'imp_feriados' => round($totalFeriados, 2),
            'almacenaje' => round($totalAlmacenaje, 2),
            'toneladas' => round($totalTnReal, 2),
            'km_total' => round($totalKmTotal, 2),
            'subsidios' => round($subsidios, 2),
            'vacaciones' => round($vacaciones, 2),
            'salario_final' => $salarioFinal,
            'detalle' => $detalle,
        ];
    }

    private function obtenerChoferesActivos()
    {
        return Bolsa::where('activo', true)
            ->where('tiene_licencia', true)
            ->whereHas('movimientosRrhh', function ($q) {
                $q->whereNull('fbaja');
            })
            ->with(['cargo:id,nombre,tarifa'])
            ->orderBy('nombrecompleto')
            ->get();
    }

    private function obtenerAforosChofer(int $idBolsa, int $mes, int $ano)
    {
        return Aforo::whereHas('cartaPorte.hojaRuta', function ($q) use ($idBolsa) {
            $q->where('id_chofer', $idBolsa)
              ->orWhere('id_chofer2', $idBolsa);
        })
        ->whereHas('cartaPorte.hojaRuta', function ($q) {
            $q->where('cancelada', false);
        })
        ->whereHas('cartaPorte', function ($q) {
            $q->where('cancelada', false);
        })
        ->whereYear('fecha_parte', $ano)
        ->whereMonth('fecha_parte', $mes)
        ->with([
            'tasa:id,tasa2',
            'cartaPorte.hojaRuta.tractivo:id,cod_tractivo',
            'cartaPorte.solicitud.lugarOrigen:id,nombre',
            'cartaPorte.solicitud.lugarDestino:id,nombre',
        ])
        ->orderBy('fecha_parte')
        ->get();
    }

    private function obtenerFeriadosMes(int $mes, int $ano): array
    {
        $mesRecord = DB::table('rh_meses')->where('idmes', $mes)->first();
        if (!$mesRecord || empty($mesRecord->dias)) return [];
        return explode(';', $mesRecord->dias);
    }

    private function obtenerIncidencias(int $idBolsa, int $mes, int $ano)
    {
        return Incidencia::where('id_bolsa', $idBolsa)
            ->whereYear('fecha_inicio', $ano)
            ->whereMonth('fecha_inicio', $mes)
            ->with('tipoIncidencia')
            ->get();
    }
}
