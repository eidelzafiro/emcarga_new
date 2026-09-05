<?php

namespace App\Services;

use App\Models\Bolsa;
use App\Models\Aforo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportePrenominaService
{
    public function __construct(
        private SalarioChoferCalcService $choferCalc,
        private SalarioAdminCalcService $adminCalc,
    ) {}

    /**
     * Prenomina de choferes transportación (sistema=2).
     * Lee de la tabla salarios y calcula campos derivados como el legacy.
     */
    public function prenominaChoferes(int $mes, int $ano, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);

        // Buscar tipo sistema pago = 2 (transportación/choferes) en catalogo_items
        $tipoSistemaPagoId = DB::table('catalogo_items')
            ->where('tipo', 'tipos_sistemas_pago')
            ->where('origen_id', 2)
            ->value('id') ?? 2;

        $salarios = DB::table('salarios')
            ->where('salarios.mes', $mes)
            ->where('salarios.ano', $ano)
            ->where('salarios.id_tipo_sistema_pago', $tipoSistemaPagoId)
            ->when(!empty($entidadesPermitidas), fn ($q) => $q->whereIn('salarios.id_entidad', $entidadesPermitidas))
            ->join('bolsa', 'salarios.id_bolsa', '=', 'bolsa.id')
            ->join('movimientos_rrhh', 'salarios.id_movimiento', '=', 'movimientos_rrhh.id')
            ->leftJoin('areas', 'salarios.id_area', '=', 'areas.id')
            ->leftJoin('cargos', 'salarios.id_cargo', '=', 'cargos.id')
            ->leftJoin('catalogo_items as csp', 'salarios.id_tipo_sistema_pago', '=', 'csp.id')
            ->select(
                'salarios.*',
                DB::raw("CONCAT(bolsa.nombre, ' ', bolsa.apellidos) as nombrecompleto"),
                'bolsa.ci as cidentidad',
                'movimientos_rrhh.nronomina',
                'areas.nombre as nombarea',
                'cargos.nombre as nombcargo',
                'csp.nombre as nombsistemapago',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(csp.extra, '$.resultados')) as resultados"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(csp.extra, '$.penaliza')) as penalizasistema"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(csp.extra, '$.cda')) as cda")
            )
            ->orderBy('bolsa.nombre')
            ->orderBy('bolsa.apellidos')
            ->get();

        $registros = [];
        $totales = [
            'ttotal' => 0, 'impbase' => 0, 'impregular' => 0, 'impirregular' => 0,
            'impplus' => 0, 'impcla' => 0, 'impbase2' => 0,
            'impiresultado' => 0, 'penresultado' => '', 'penimporte' => 0,
            'impresultado' => 0, 'impgps' => 0, 'impferiados' => 0,
            'impreporte' => 0,
        ];

        foreach ($salarios as $arr) {
            $ttotal = (float) $arr->t_total;
            if ($ttotal <= 0) continue;

            $impregular = (float) $arr->imp_regular;
            $impirregular = (float) $arr->imp_irregular;
            $impplus = (float) $arr->imp_plus;
            $impcla = (float) $arr->imp_cla;
            $impnocturnidad = round((float) $arr->imp_nocturna_1 + (float) $arr->imp_nocturna_2, 2);
            $impferiados = (float) $arr->imp_feriados;
            $impdoblaje = (float) $arr->imp_doblaje;
            $impmaestrias = (float) $arr->imp_maestrias;
            $impadicional = (float) $arr->imp_adicional;
            $impgps = (float) $arr->imp_gps;

            // Imp base = regular + irregular
            $impbase = round($impregular + $impirregular, 2);

            // Imp base2 = regular + irregular + plus + cla
            $impbase2 = round($impregular + $impirregular + $impplus + $impcla, 2);

            // Salario tiempo (strt) = regular + irregular + plus + cla + nocturnidad + feriados + doblaje
            $strt = round($impregular + $impirregular + $impplus + $impcla + $impnocturnidad + $impferiados + $impdoblaje, 2);

            // Coeficiente (ac = ri + cpl)
            $ri = (float) $arr->ri;
            $cpl = (float) $arr->cpl;
            if ($ri > 1) $ri = round($ri / 100, 2);
            if ($cpl > 1) $cpl = round($cpl / 100, 2);
            $ac = round($ri + $cpl, 2);

            // Salario con coeficiente
            $sc = round($strt * $ac, 2);

            // Resultados
            $resultados = (float) $arr->resultados;
            $sr = $resultados > 0 ? round($sc * $resultados, 2) : 0;

            // Penalizaciones
            $penresultado = '';
            $penimporte = 0;

            // Penalización del sistema
            $penalizasistema = (float) ($arr->penalizasistema ?? 0);
            if ($penalizasistema > 0) {
                $penresultado = $penalizasistema;
                $penimporte = round(($penalizasistema / 100) * $sr, 2);
            }

            // Penalización del área (sobreescribe la del sistema)
            $penalizaarea = (float) ($arr->penalizaarea ?? 0);
            if ($penalizaarea > 0) {
                $penresultado = $penalizaarea;
                $penimporte = round(($penalizaarea / 100) * $sr, 2);
            }

            // Penalización individual del chofer (sobreescribe todo)
            $penreschofer = DB::table('penalizaciones')
                ->join('tipos_penalizaciones', 'penalizaciones.id_tipo_penalizacion', '=', 'tipos_penalizaciones.id')
                ->whereYear('penalizaciones.fecha', $ano)
                ->whereMonth('penalizaciones.fecha', $mes)
                ->where('penalizaciones.id_bolsa', $arr->id_bolsa)
                ->where('tipos_penalizaciones.tipo_pago_adicional_id', 7)
                ->select(DB::raw('SUM(penalizaciones.importe) as importe'))
                ->value('importe');

            if ($penreschofer !== null && $penreschofer > 0) {
                $penreschofer = min($penreschofer, 100);
                $penresultado = $penreschofer;
                $penimporte = round(($penreschofer * $sr) / 100, 2);
            }

            // Padicionales = gps + maestrias + adicional
            $padicionales = round($impgps + $impmaestrias + $impadicional, 2);

            // Resultados finales
            $srr = round($sr - $penimporte, 2);
            $sd = round($srr + $strt, 2);
            $st = round($sd + $padicionales, 2);
            $cda = (float) ($arr->cda ?? 0);
            $sta = round($st - ($st * $cda), 2);

            // Impsalfinal = imp regular + irregular + plus + padicionales (sistema != 2)
            $impsalfinal = round($impregular + $impirregular + $impplus + $padicionales, 2);

            // Imp resultado (si hay resultados)
            $impiresultado = 0;
            $impresultado = 0;
            if ($resultados > 0) {
                $impres = round($impsalfinal - ($impgps + $impmaestrias), 2);
                $impiresultado = round($resultados * $impres, 2);

                // Penalizaciones sobre resultado
                if ($penalizasistema > 0) {
                    $penresultado = $penalizasistema;
                    $penimporte = round(($penalizasistema / 100) * $impiresultado, 2);
                }
                if ($penalizaarea > 0) {
                    $penresultado = $penalizaarea;
                    $penimporte = round(($penalizaarea / 100) * $impiresultado, 2);
                }
                if ($penreschofer !== null && $penreschofer > 0) {
                    $penimporte = round(($penreschofer * $impiresultado) / 100, 2);
                }

                $impresultado = $impiresultado - $penimporte;
            }

            // Imp reporte = impsalfinal + impresultado
            $impreporte = round($impsalfinal + $impresultado, 2);

            $registros[] = [
                'id_bolsa' => $arr->id_bolsa,
                'nronomina' => $arr->nronomina ?? '',
                'nombrecompleto' => $arr->nombrecompleto ?? '',
                'cidentidad' => $arr->cidentidad ?? '',
                'nombarea' => $arr->nombarea ?? '',
                'nombcargo' => $arr->nombcargo ?? '',
                'nombsistemapago' => $arr->nombsistemapago ?? '',
                'ttotal' => $ttotal,
                'impregular' => $impregular,
                'impirregular' => $impirregular,
                'impplus' => $impplus,
                'impcla' => $impcla,
                'impnocturnidad' => $impnocturnidad,
                'impferiados' => $impferiados,
                'impdoblaje' => $impdoblaje,
                'impmaestrias' => $impmaestrias,
                'impadicional' => $impadicional,
                'impgps' => $impgps,
                'impbase' => $impbase,
                'impbase2' => $impbase2,
                'strt' => $strt,
                'ri' => $ri,
                'cpl' => $cpl,
                'ac' => $ac,
                'sc' => $sc,
                'resultados' => $resultados,
                'sr' => $sr,
                'penresultado' => $penresultado,
                'penimporte' => $penimporte,
                'padicionales' => $padicionales,
                'srr' => $srr,
                'sd' => $sd,
                'st' => $st,
                'sta' => $sta,
                'impsalfinal' => $impsalfinal,
                'impiresultado' => $impiresultado,
                'impresultado' => $impresultado,
                'impreporte' => $impreporte,
                'cda' => $cda,
            ];

            $totales['ttotal'] += $ttotal;
            $totales['impbase'] += $impbase;
            $totales['impregular'] += $impregular;
            $totales['impirregular'] += $impirregular;
            $totales['impplus'] += $impplus;
            $totales['impcla'] += $impcla;
            $totales['impbase2'] += $impbase2;
            $totales['impiresultado'] += $impiresultado;
            $totales['penimporte'] += $penimporte;
            $totales['impresultado'] += $impresultado;
            $totales['impgps'] += $impgps;
            $totales['impferiados'] += $impferiados;
            $totales['impreporte'] += $impreporte;
        }

        $titulo = 'DATOS P/NOMINAS SALARIO TRANSPORTACION';
        if ($registros) {
            $titulo = 'DATOS P/NOMINAS CALCULADAS  ' . ($registros[0]['nombsistemapago'] ?? '');
        }

        return [
            'titulo' => $titulo,
            'periodo' => Carbon::createFromDate($ano, $mes, 1)->format('F Y'),
            'registros' => $registros,
            'totales' => $totales,
        ];
    }

    /**
     * Prenomina personal administrativo.
     * Lee de la tabla salarios y calcula campos derivados como el legacy.
     */
    public function prenominaAdministrativo(int $mes, int $ano, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);

        // Excluir sistemas de pago = 2 (transportación/choferes)
        // y sistemas = 1 (CUC)
        $sistemasExcluir = DB::table('catalogo_items')
            ->where('tipo', 'tipos_sistemas_pago')
            ->whereIn('origen_id', [1, 2])
            ->pluck('id')
            ->toArray();
        if (empty($sistemasExcluir)) {
            $sistemasExcluir = [1, 2];
        }

        $salarios = DB::table('salarios')
            ->where('salarios.mes', $mes)
            ->where('salarios.ano', $ano)
            ->whereNotIn('salarios.id_tipo_sistema_pago', $sistemasExcluir)
            ->when(!empty($entidadesPermitidas), fn ($q) => $q->whereIn('salarios.id_entidad', $entidadesPermitidas))
            ->join('bolsa', 'salarios.id_bolsa', '=', 'bolsa.id')
            ->join('movimientos_rrhh', 'salarios.id_movimiento', '=', 'movimientos_rrhh.id')
            ->leftJoin('areas', 'salarios.id_area', '=', 'areas.id')
            ->leftJoin('cargos', 'salarios.id_cargo', '=', 'cargos.id')
            ->leftJoin('catalogo_items as csp', 'salarios.id_tipo_sistema_pago', '=', 'csp.id')
            ->select(
                'salarios.*',
                DB::raw("CONCAT(bolsa.nombre, ' ', bolsa.apellidos) as nombrecompleto"),
                'bolsa.ci as cidentidad',
                'movimientos_rrhh.nronomina',
                'areas.nombre as nombarea',
                'cargos.nombre as nombcargo',
                'csp.nombre as nombsistemapago',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(csp.extra, '$.resultados')) as resultados"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(csp.extra, '$.penaliza')) as penalizasistema"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(csp.extra, '$.cda')) as cda")
            )
            ->orderBy('areas.nombre')
            ->orderBy('bolsa.nombre')
            ->orderBy('bolsa.apellidos')
            ->get();

        $registros = [];
        $porArea = [];
        $totales = [
            'ttotal' => 0, 'impbase' => 0, 'impregular' => 0, 'impirregular' => 0,
            'impplus' => 0, 'impcla' => 0, 'impbase2' => 0,
            'impiresultado' => 0, 'penresultado' => '', 'penimporte' => 0,
            'impresultado' => 0, 'impgps' => 0, 'impferiados' => 0,
            'impreporte' => 0, 'padicionales' => 0,
        ];

        foreach ($salarios as $arr) {
            $ttotal = (float) $arr->t_total;

            $impregular = (float) $arr->imp_regular;
            $impirregular = (float) $arr->imp_irregular;
            $impplus = (float) $arr->imp_plus;
            $impcla = (float) $arr->imp_cla;
            $impnocturnidad = round((float) $arr->imp_nocturna_1 + (float) $arr->imp_nocturna_2, 2);
            $impferiados = (float) $arr->imp_feriados;
            $impdoblaje = (float) $arr->imp_doblaje;
            $impmaestrias = (float) $arr->imp_maestrias;
            $impadicional = (float) $arr->imp_adicional;
            $impgps = (float) $arr->imp_gps;
            $impgelectro = (float) $arr->imp_g_electro;
            $impGarantia = (float) $arr->imp_garantia;
            $impHExtra = (float) $arr->imp_h_extra;

            // Imp base = regular + irregular
            $impbase = round($impregular + $impirregular, 2);

            // Imp base2 = regular + irregular + plus + cla
            $impbase2 = round($impregular + $impirregular + $impplus + $impcla, 2);

            // Padicionales = adicional + gps + cla + nocturnidad + gelectro + maestrias + feriados + doblaje
            $padicionales = round($impadicional + $impgps + $impcla + $impnocturnidad + $impgelectro + $impmaestrias + $impferiados + $impdoblaje, 2);

            // Impsalfinal = imp regular + irregular + plus + padicionales
            $impsalfinal = round($impregular + $impirregular + $impplus + $padicionales, 2);

            // Imp resultado
            $resultados = (float) $arr->resultados;
            $impiresultado = 0;
            $impresultado = 0;
            $penresultado = '';
            $penimporte = 0;

            if ($resultados > 0) {
                $impres = round($impsalfinal - ($impgps + $impmaestrias), 2);
                $impiresultado = round($resultados * $impres, 2);

                // Penalizaciones
                $penalizasistema = (float) ($arr->penalizasistema ?? 0);
                if ($penalizasistema > 0) {
                    $penresultado = $penalizasistema;
                    $penimporte = round(($penalizasistema / 100) * $impiresultado, 2);
                }

                $penalizaarea = (float) ($arr->penalizaarea ?? 0);
                if ($penalizaarea > 0) {
                    $penresultado = $penalizaarea;
                    $penimporte = round(($penalizaarea / 100) * $impiresultado, 2);
                }

                $penresadmin = DB::table('penalizaciones')
                    ->join('tipos_penalizaciones', 'penalizaciones.id_tipo_penalizacion', '=', 'tipos_penalizaciones.id')
                    ->whereYear('penalizaciones.fecha', $ano)
                    ->whereMonth('penalizaciones.fecha', $mes)
                    ->where('penalizaciones.id_bolsa', $arr->id_bolsa)
                    ->where('tipos_penalizaciones.tipo_pago_adicional_id', 7)
                    ->select(DB::raw('SUM(penalizaciones.importe) as importe'))
                    ->value('importe');

                if ($penresadmin !== null && $penresadmin > 0) {
                    $penresadmin = min($penresadmin, 100);
                    $penimporte = round(($penresadmin * $impiresultado) / 100, 2);
                }

                $impresultado = $impiresultado - $penimporte;
            }

            // Imp reporte = impsalfinal + impresultado
            $impreporte = round($impsalfinal + $impresultado, 2);

            $registro = [
                'id_bolsa' => $arr->id_bolsa,
                'nronomina' => $arr->nronomina ?? '',
                'nombrecompleto' => $arr->nombrecompleto ?? '',
                'cidentidad' => $arr->cidentidad ?? '',
                'nombarea' => $arr->nombarea ?? '',
                'nombcargo' => $arr->nombcargo ?? '',
                'nombsistemapago' => $arr->nombsistemapago ?? '',
                'ttotal' => $ttotal,
                'impregular' => $impregular,
                'impirregular' => $impirregular,
                'impplus' => $impplus,
                'impcla' => $impcla,
                'impnocturnidad' => $impnocturnidad,
                'impferiados' => $impferiados,
                'impdoblaje' => $impdoblaje,
                'impmaestrias' => $impmaestrias,
                'impadicional' => $impadicional,
                'impgps' => $impgps,
                'impgelectro' => $impgelectro,
                'impbase' => $impbase,
                'impbase2' => $impbase2,
                'padicionales' => $padicionales,
                'impsalfinal' => $impsalfinal,
                'resultados' => $resultados,
                'impiresultado' => $impiresultado,
                'penresultado' => $penresultado,
                'penimporte' => $penimporte,
                'impresultado' => $impresultado,
                'impreporte' => $impreporte,
            ];

            $registros[] = $registro;

            $area = $arr->nombarea ?? 'Sin área';
            $porArea[$area][] = $registro;

            $totales['ttotal'] += $ttotal;
            $totales['impbase'] += $impbase;
            $totales['impregular'] += $impregular;
            $totales['impirregular'] += $impirregular;
            $totales['impplus'] += $impplus;
            $totales['impcla'] += $impcla;
            $totales['impbase2'] += $impbase2;
            $totales['padicionales'] += $padicionales;
            $totales['impiresultado'] += $impiresultado;
            $totales['penimporte'] += $penimporte;
            $totales['impresultado'] += $impresultado;
            $totales['impgps'] += $impgps;
            $totales['impferiados'] += $impferiados;
            $totales['impreporte'] += $impreporte;
        }

        $titulo = 'DATOS P/NOMINAS CALCULADAS  ';
        if ($registros) {
            $titulo = 'DATOS P/NOMINAS CALCULADAS  ' . ($registros[0]['nombsistemapago'] ?? '');
        }

        return [
            'titulo' => $titulo,
            'periodo' => Carbon::createFromDate($ano, $mes, 1)->format('F Y'),
            'registros' => $registros,
            'por_area' => $porArea,
            'totales' => $totales,
        ];
    }

    /**
     * Modelo 1 — Control de transportaciones por chofer.
     * Detalle de cartas de porte del mes, con tiempos, costos y salarios.
     */
    public function modelo1(int $mes, int $ano, ?int $idBolsa = null): array
    {
        $query = Aforo::whereYear('fecha_parte', $ano)
            ->whereMonth('fecha_parte', $mes)
            ->whereHas('cartaPorte', fn ($cp) => $cp->where('cancelada', false))
            ->with([
                'cartaPorte:id,cancelada,numero,id_hoja_ruta,id_solicitud,fecha_emision,distancia',
                'cartaPorte.cliente:nombre',
                'cartaPorte.hojaRuta:id,numero,id_tractivo,id_arrastre,id_chofer,id_chofer2',
                'cartaPorte.hojaRuta.tractivo:id,codigo,placa',
                'cartaPorte.hojaRuta.arrastre:id,codigo,placa',
                'cartaPorte.hojaRuta.chofer:id,nombre,apellidos',
                'cartaPorte.hojaRuta.chofer2:id,nombre,apellidos',
                'cartaPorte.solicitud:id,id_lugar_origen,id_lugar_destino',
                'cartaPorte.solicitud.lugarOrigen:id,nombre',
                'cartaPorte.solicitud.lugarDestino:id,nombre',
                'cartaPorte.tipoCarga:nombre',
                'cartaPorte.producto:nombre',
                'tasa:id,nombre,tasa,tasa2',
            ]);

        if ($idBolsa) {
            $query->where(function ($q) use ($idBolsa) {
                $q->whereHas('cartaPorte.hojaRuta', fn ($hr) => $hr->where('id_chofer', $idBolsa))
                  ->orWhereHas('cartaPorte.hojaRuta', fn ($hr) => $hr->where('id_chofer2', $idBolsa));
            });
        }

        $aforos = $query->orderBy('fecha_parte')
            ->orderBy('id_carta_porte')
            ->get();

        // Agrupar por chofer
        $porChofer = [];
        foreach ($aforos as $aforo) {
            $cp = $aforo->cartaPorte;
            if (!$cp) continue;

            $hr = $cp->hojaRuta;
            $choferId = $hr?->id_chofer;
            $chofer = $hr?->chofer;
            $nombreChofer = $chofer ? trim($chofer->nombre . ' ' . $chofer->apellidos) : 'Desconocido';

            if (!isset($porChofer[$choferId])) {
                $bolsa = Bolsa::find($choferId);
                $porChofer[$choferId] = [
                    'id_bolsa' => $choferId,
                    'nombre' => $nombreChofer,
                    'carnet' => $bolsa?->ci ?? '',
                    'registros' => [],
                    'totales' => [
                        'ingresos' => 0, 'salario_cp' => 0, 'km_total' => 0,
                        'toneladas' => 0, 'tiempo_total' => 0,
                    ],
                ];
            }

            $registro = [
                'fecha' => $aforo->fecha_parte,
                'nro_cp' => $cp->numero ?? '',
                'nro_hr' => $hr?->numero ?? '',
                'tractivo' => $hr?->tractivo?->codigo ?? '',
                'arrastre' => $hr?->arrastre?->codigo ?? '',
                'equipo' => trim(($hr?->tractivo?->codigo ?? '') . ' / ' . ($hr?->arrastre?->codigo ?? ''), ' /'),
                'cliente' => $cp->cliente?->nombre ?? '',
                'origen' => $cp->solicitud?->lugarOrigen?->nombre ?? '',
                'destino' => $cp->solicitud?->lugarDestino?->nombre ?? '',
                'producto' => $cp->producto?->nombre ?? '',
                'tipo_carga' => $cp->tipoCarga?->nombre ?? '',
                'distancia' => $cp->distancia ?? 0,
                'km_total' => $aforo->km_total_total ?? $aforo->km_total ?? 0,
                'tn_real' => $aforo->tn_real_total ?? $aforo->tn_real ?? 0,
                'tiempo_total' => $aforo->tiempo_total ?? 0,
                'ingreso' => $aforo->ingreso_mt ?? 0,
                'tasa_nombre' => $aforo->tasa?->nombre ?? '',
                'tasa_valor' => $aforo->tasa?->tasa ?? 0,
                'salario' => $aforo->salario ?? 0,
                'almacenaje' => $aforo->almacenaje ?? 0,
                'es_feriado' => (bool) ($aforo->tiempo_feriado ?? 0),
                'doble_chofer' => ($hr?->id_chofer2 && $hr->id_chofer2 != $hr->id_chofer),
            ];

            $porChofer[$choferId]['registros'][] = $registro;

            $porChofer[$choferId]['totales']['ingresos'] += $registro['ingreso'];
            $porChofer[$choferId]['totales']['salario_cp'] += $registro['salario'];
            $porChofer[$choferId]['totales']['km_total'] += $registro['km_total'];
            $porChofer[$choferId]['totales']['toneladas'] += $registro['tn_real'];
            $porChofer[$choferId]['totales']['tiempo_total'] += $registro['tiempo_total'];
        }

        // Ordenar por nombre
        uasort($porChofer, fn ($a, $b) => strcmp($a['nombre'], $b['nombre']));

        return [
            'titulo' => 'MODELO 1 - CONTROL TRANSPORTACIONES CHOFERES',
            'periodo' => Carbon::createFromDate($ano, $mes, 1)->format('F Y'),
            'por_chofer' => array_values($porChofer),
        ];
    }

    private function entidadActivaId(): ?int
    {
        return (int) session('entidad_activa_id') ?: null;
    }

    private function entidadesPermitidas(?int $entidadId = null): array
    {
        $id = $entidadId ?? $this->entidadActivaId();
        if (!$id) return [];

        $entidad = \App\Models\Entidad::find($id);
        if (!$entidad) return [$id];

        $hijas = \App\Models\Entidad::where('parent_id', $id)->pluck('id')->toArray();
        return array_merge([$id], $hijas);
    }
}
