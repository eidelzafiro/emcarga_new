<?php

namespace App\Services;

use App\Models\Bolsa;
use App\Models\Aforo;
use App\Models\Incidencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportePrenominaService
{
    /**
     * El legacy usa `session('nroci')` para decidir si la columna EXP muestra
     * el CI (nroci=1) o el versat/nronomina. En el reporte de incidencias la
     * referencia muestra el CI, así que se usa por defecto nroci=1.
     */
    private bool $nroci;

    public function __construct(
        private SalarioChoferCalcService $choferCalc,
        private SalarioAdminCalcService $adminCalc,
    ) {
        $this->nroci = ((int) session('nroci', 1)) === 1;
    }

    /**
     * Prenomina de choferes transportación (sistema=2).
     * Lee de la tabla salarios y calcula campos derivados como el legacy.
     */
    public function prenominaChoferes(int $mes, int $ano, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);

        // Id del catálogo "RESULTADO CHOFERES" (tipo_pago_adicional para penalizar).
        $idPagoResultadoChoferes = DB::table('catalogo_items')
            ->where('tipo', 'tipos_pagos_adicionales')
            ->where('origen_id', 6)
            ->value('id');

        // Choferes del sistema 2 (CHOFERES TRANSPORTACION) con movimiento vigente.
        $choferes = Bolsa::query()
            ->where('activo', true)
            ->whereHas('area', fn ($q) => $q->where('id_tipo_sistema_pago', 2))
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->when(! empty($entidadesPermitidas),
                fn ($q) => $q->whereIn('id_entidad', $entidadesPermitidas),
                fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['cargo:id,nombre,tarifa,cla'])
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get();

        $registros = [];
        $totales = [
            'ttotal' => 0, 'escala' => 0, 'cla' => 0, 'strt' => 0,
            'ingresos' => 0, 'fondo' => 0, 'is_inicial' => 0,
            'pen_importe' => 0, 'is_final' => 0, 'sd' => 0,
            'ft' => 0, 'salario' => 0, 'dt' => 0,
        ];

        foreach ($choferes as $chofer) {
            $res = $this->choferCalc->calcularSalarioChofer($chofer->id, $mes, $ano);
            if (! $res) {
                continue;
            }

            $tarifa = (float) ($chofer->cargo?->tarifa ?? 0);

            $ttotal = (float) $res['t_total'];
            $regular = (float) $res['regular'];
            $irregular = (float) $res['irregular'];

            $impregular = round($regular * $tarifa, 2);
            $impirregular = round($irregular * $tarifa, 2);
            $impcla = round((float) $res['imp_cla'], 2);
            $impnocturnidad = round((float) $res['imp_nocturnidad_1'] + (float) $res['imp_nocturnidad_2'], 2);

            $impbase = round($impregular + $impirregular + $impcla, 2);
            $impbase2 = round($impbase + $impnocturnidad, 2);

            $tsalario = round((float) $res['salario_cp'], 2);
            $ingresos = round((float) $res['ingresos'], 2);

            // Incremento salarial inicial (resultado): fondo - salario base.
            $impiresultado = round($tsalario - $impbase2, 2);
            if ($impiresultado < 0) {
                $impiresultado = 0;
            }

            // Penalización por resultado de choferes (pago adicional RESULTADO CHOFERES).
            $penresultado = '';
            $penimporte = 0;
            if ($idPagoResultadoChoferes && $impiresultado > 0) {
                $penImporteSum = DB::table('penalizaciones')
                    ->where('id_bolsa', $chofer->id)
                    ->whereYear('fecha', $ano)
                    ->whereMonth('fecha', $mes)
                    ->whereIn('id_tipo_penalizacion', function ($q) use ($idPagoResultadoChoferes) {
                        $q->select('id')->from('tipos_penalizaciones')
                            ->where('tipo_pago_adicional_id', $idPagoResultadoChoferes);
                    })
                    ->sum('importe');

                if ($penImporteSum > 0) {
                    $penImporteSum = min($penImporteSum, 100);
                    $penresultado = $penImporteSum;
                    $penimporte = round(($penImporteSum * $impiresultado) / 100, 2);
                }
            }

            $impresultado = round($impiresultado - $penimporte, 2);
            if ($impresultado < 0) {
                $impresultado = 0;
            }

            $impferiados = round((float) $res['imp_feriados'], 2);

            $sd = round($impbase2 + $impresultado, 2);
            $st = round($impbase2 + $impferiados + $impresultado, 2);

            $dt = $this->diasTrabajados($chofer->id, $mes, $ano);

            $registros[] = [
                'id_bolsa' => $chofer->id,
                'exp' => $chofer->versat ?? '',
                'nombrecompleto' => $chofer->nombrecompleto,
                'nombcargo' => $chofer->cargo?->nombre ?? '',
                'tarifa' => $tarifa,
                'ttotal' => $ttotal,
                'escala' => round($impregular + $impirregular, 2),
                'cla' => $impcla,
                'strt' => $impbase,
                'ingresos' => $ingresos,
                'fondo' => $tsalario,
                'is_inicial' => $impiresultado,
                'pen_resultado' => $penresultado,
                'pen_importe' => $penimporte,
                'is_final' => $impresultado,
                'sd' => $sd,
                'ft' => $impferiados,
                'salario' => $st,
                'dt' => $dt,
            ];

            $totales['ttotal'] += $ttotal;
            $totales['escala'] += round($impregular + $impirregular, 2);
            $totales['cla'] += $impcla;
            $totales['strt'] += $impbase;
            $totales['ingresos'] += $ingresos;
            $totales['fondo'] += $tsalario;
            $totales['is_inicial'] += $impiresultado;
            $totales['pen_importe'] += $penimporte;
            $totales['is_final'] += $impresultado;
            $totales['sd'] += $sd;
            $totales['ft'] += $impferiados;
            $totales['salario'] += $st;
            $totales['dt'] += $dt;
        }

        foreach ($totales as $k => $v) {
            $totales[$k] = round($v, 2);
        }

        return [
            'titulo' => 'PRENOMINA SALARIO TRANSPORTACION',
            'periodo' => Carbon::createFromDate($ano, $mes, 1)->format('F Y'),
            'registros' => $registros,
            'totales' => $totales,
        ];
    }

    /**
     * Cuenta los días trabajados ('T') del chofer en el mes, a partir de las
     * fechas de carga/descarga de sus cartas de porte (réplica mostrar_control).
     */
    private function diasTrabajados(int $idBolsa, int $mes, int $ano): int
    {
        $diasMes = Carbon::createFromDate($ano, $mes, 1)->daysInMonth;
        $tiempo = array_fill(0, $diasMes, '');

        // Réplica mostrar_control: filtra por mes de emisión de la CP y por mes
        // de carga/descarga (excluye aforos que cruzan de mes).
        $filas = Aforo::query()
            ->whereHas('cartaPorte', fn ($q) => $q->where('cancelada', false)
                ->whereYear('fecha_emision', $ano)
                ->whereMonth('fecha_emision', $mes))
            ->where(function ($q) use ($idBolsa) {
                $q->whereHas('cartaPorte', fn ($cp) => $cp->where('id_chofer', $idBolsa))
                  ->orWhereHas('cartaPorte', fn ($cp) => $cp->where('id_chofer2', $idBolsa));
            })
            ->whereMonth('fecha_carga', $mes)
            ->whereMonth('fecha_descarga', $mes)
            ->with('cartaPorte:id,id_chofer,id_chofer2,cancelada')
            ->get();

        foreach ($filas as $aforo) {
            $fcarga = $aforo->fecha_carga;
            $fdescarga = $aforo->fecha_descarga;
            if (! $fcarga || ! $fdescarga) {
                continue;
            }
            $inicio = (int) $fcarga->format('j');
            $final = (int) $fdescarga->format('j');
            for ($d = $inicio; $d <= $final; $d++) {
                if ($d >= 1 && $d <= $diasMes) {
                    $tiempo[$d - 1] = 'T';
                }
            }
        }

        return count(array_filter($tiempo, fn ($v) => $v === 'T'));
    }

    /**
     * Réplica de mostrar_control (ModSalarioChofer.php:828): devuelve la fila
     * de control del ANÁLISIS DEL TIEMPO DE TRABAJO del Modelo 1, con un valor
     * por día del mes (índices 0..dias-1): 'D' para domingo, 'T' para aforos
     * del chofer, y la clave de incidencia (substr 0,2) en el resto.
     */
    public function controlDias(int $idBolsa, int $mes, int $ano, ?int $entidadId = null): array
    {
        $diasMes = Carbon::createFromDate($ano, $mes, 1)->daysInMonth;
        $tiempo = array_fill(0, $diasMes, '');

        // Dominicos: 'D' (réplica date('w', mktime(...)) == 0).
        for ($i = 0; $i < $diasMes; $i++) {
            if (date('w', mktime(0, 0, 0, $mes, $i + 1, $ano)) == 0) {
                $tiempo[$i] = 'D';
            }
        }

        $entidades = $this->entidadesPermitidas($entidadId);

        // Aforos del chofer: 'T' de fecha_carga a fecha_descarga (id_chofer/id_chofer2).
        $filas = Aforo::query()
            ->whereHas('cartaPorte', fn ($q) => $q->where('cancelada', false)
                ->whereYear('fecha_emision', $ano)
                ->whereMonth('fecha_emision', $mes))
            ->where(function ($q) use ($idBolsa) {
                $q->whereHas('cartaPorte', fn ($cp) => $cp->where('id_chofer', $idBolsa))
                    ->orWhereHas('cartaPorte', fn ($cp) => $cp->where('id_chofer2', $idBolsa));
            })
            ->whereMonth('fecha_carga', $mes)
            ->whereMonth('fecha_descarga', $mes)
            ->when(! empty($entidades),
                fn ($q) => $q->whereHas('cartaPorte.hojaRuta.tractivo', fn ($t) => $t->whereIn('tractivos.id_entidad', $entidades)))
            ->with('cartaPorte:id,id_chofer,id_chofer2,cancelada')
            ->get();

        foreach ($filas as $aforo) {
            $fcarga = $aforo->fecha_carga;
            $fdescarga = $aforo->fecha_descarga;
            if (! $fcarga || ! $fdescarga) {
                continue;
            }
            $inicio = (int) $fcarga->format('j');
            $final = (int) $fdescarga->format('j');
            for ($d = $inicio; $d <= $final; $d++) {
                if ($d >= 1 && $d <= $diasMes && $tiempo[$d - 1] !== 'D') {
                    $tiempo[$d - 1] = 'T';
                }
            }
        }

        // Incidencias del mes: sobreescriben con substr(clave,0,2) salvo domingo.
        $incidencias = Incidencia::query()
            ->where('id_bolsa', $idBolsa)
            ->whereYear('fecha_inicio', $ano)
            ->whereMonth('fecha_inicio', $mes)
            ->where(fn ($q) => $q->where('periodo_actual', '>', 0)->orWhere('importe', '>', 0))
            ->when(! empty($entidades),
                fn ($q) => $q->whereHas('bolsa', fn ($b) => $b->whereIn('id_entidad', $entidades)))
            ->with('tipoIncidencia:id,extra')
            ->get();

        foreach ($incidencias as $inc) {
            $clave = substr((string) ($inc->tipoIncidencia?->extra['clave'] ?? ''), 0, 2);
            if ($clave === '') {
                continue;
            }
            $fInicio = $inc->fecha_inicio ? (int) $inc->fecha_inicio->format('j') : 0;
            $fFin = $inc->fecha_fin ? (int) $inc->fecha_fin->format('j') : $fInicio;
            for ($d = $fInicio; $d <= $fFin; $d++) {
                if ($d >= 1 && $d <= $diasMes && $tiempo[$d - 1] !== 'D') {
                    $tiempo[$d - 1] = $clave;
                }
            }
        }

        return $tiempo;
    }

    /**
     * Prenómina personal administrativo (sistema ≠ transportación).
     *
     * Calcula en vivo con SalarioAdminCalcService (la misma fuente que usa la
     * pantalla Salarios Administrativos), en lugar de leer la tabla `salarios`
     * (que no se migró del legacy y está vacía). Las columnas del PDF/Excel se
     * derivan de los importes del cálculo individual de cada empleado.
     */
    public function prenominaAdministrativo(int $mes, int $ano, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);

        // Solo trabajadores administrativos (sistema de pago = 1). Los choferes
        // (2=transportación) y la paquetería (3) se calculan en prenóminas aparte.
        // Y SOLO de la entidad activa (o sus subordinadas). Sin entidad activa
        // no se devuelve nada.
        $empleados = Bolsa::query()
            ->where('activo', true)
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->whereHas('area', fn ($q) => $q->where('id_tipo_sistema_pago', 1))
            ->when(! empty($entidadesPermitidas),
                fn ($q) => $q->whereIn('id_entidad', $entidadesPermitidas),
                fn ($q) => $q->whereRaw('1 = 0'))
            ->with([
                'cargo:id,nombre,tarifa,cla,en_salario,id_categoria_cargo,id_fondo_tiempo,id_nivel_educacion,id_grupo_horario',
                'cargo.categoria_cargo:id,nombre',
                'cargo.fondo_tiempo:id,fondo_tiempo',
                'cargo.nivel_educacion:id,origen_id',
                'area:id,nombre,orden',
            ])
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get();

        $calculados = [];
        foreach ($empleados as $empleado) {
            $c = $this->adminCalc->calcularSalarioEmpleado($empleado, $mes, $ano, $entidadId);
            if ($c) {
                $calculados[] = $c;
            }
        }

        // nronomina por movimiento vigente (columna EXP del reporte).
        $idMovs = collect($calculados)->pluck('id_movimiento')->unique()->filter()->values();
        $nronominas = $idMovs->isEmpty()
            ? collect()
            : \App\Models\MovimientoRrhh::whereIn('id', $idMovs)->pluck('nronomina', 'id');

        $registros = [];
        $porArea = [];
        $totales = [
            'salario' => 0, 'tarifa' => 0, 'cla' => 0, 'ttotal' => 0,
            'impescala' => 0, 'impcla' => 0, 'impnocturnidad' => 0,
            'tiempoextra' => 0, 'impextra' => 0, 'impmaestrias' => 0,
            'impsalario2' => 0,
        ];

        // CI (carnet) y orden de área por empleado, para CARNET y el orden del reporte.
        $cis = $empleados->pluck('ci', 'id');
        $versats = $empleados->pluck('versat', 'id');
        $areaOrden = [];
        foreach ($empleados as $e) {
            $areaOrden[$e->id] = (int) ($e->area->orden ?? 0);
        }

        foreach ($calculados as $c) {
            $idMov = $c['id_movimiento'] ?? null;
            $nronomina = $idMov ? ($nronominas[$idMov] ?? '') : '';

            $ttotal = (float) ($c['ttotal'] ?? 0);
            $impescala = round((float) ($c['impescala'] ?? 0), 2);
            $impcla = round((float) ($c['imp_cla'] ?? 0), 2);
            $impnocturnidad = round((float) ($c['imp_nocturnidad'] ?? 0), 2);
            $impextra = round((float) ($c['imp_extra'] ?? 0), 2);
            $impmaestrias = round((float) ($c['imp_maestrias'] ?? 0), 2);
            $impsalario2 = round((float) ($c['impsalario2'] ?? 0), 2);
            $impdoblaje = round((float) ($c['imp_doblaje'] ?? 0), 2);
            $impferiados = round((float) ($c['imp_feriados'] ?? 0) + (float) ($c['imp_feriados_t'] ?? 0), 2);
            $impincidencia = round((float) ($c['imp_incidencia'] ?? 0), 2);
            // Columna "OTRAS" del reporte ADICIONALES: el legacy usa la incidencia.
            $impotras = $impincidencia;
            // `padicionales2` = adicionales + CLA (réplica ModSalarioAdmin:968-970).
            $padicionales2 = round((float) ($c['padicionales'] ?? 0) + $impcla, 2);
            // Nocturnidad desglosada (reporte DATOS P/NOMINAS (NOCTURNIDAD)).
            $noct1 = round((float) ($c['noct1'] ?? 0), 2);
            $noct2 = round((float) ($c['noct2'] ?? 0), 2);
            $impnoct1 = round((float) ($c['imp_nocturnidad_1'] ?? 0), 2);
            $impnoct2 = round((float) ($c['imp_nocturnidad_2'] ?? 0), 2);

            $registro = [
                'id_bolsa' => $c['id_bolsa'] ?? null,
                'area_orden' => $areaOrden[$c['id_bolsa'] ?? 0] ?? 0,
                'nronomina' => $nronomina,
                'nombrecompleto' => $c['nombre_completo'] ?? '',
                'cidentidad' => $cis[$c['id_bolsa'] ?? 0] ?? '',
                'versat' => $versats[$c['id_bolsa'] ?? 0] ?? '',
                'nombarea' => $c['area'] ?? '',
                'nombcargo' => $c['cargo'] ?? '',
                'salario' => round((float) ($c['salario_escala'] ?? 0), 2),
                'tarifa' => round((float) ($c['tarifa'] ?? 0), 4),
                'cla' => round((float) ($c['cla'] ?? 0), 2),
                'ttotal' => round($ttotal, 2),
                'impescala' => $impescala,
                'impcla' => $impcla,
                'impnocturnidad' => $impnocturnidad,
                'tarhextras' => round((float) ($c['tarhextras'] ?? 0), 4),
                'tiempoextra' => round((float) ($c['tiempoextra'] ?? 0), 2),
                'impextra' => $impextra,
                'impmaestrias' => $impmaestrias,
                'impsalario2' => $impsalario2,
                'impdoblaje' => $impdoblaje,
                'impferiados' => $impferiados,
                'impincidencia' => $impincidencia,
                'impotras' => $impotras,
                'padicionales2' => $padicionales2,
                'noct1' => $noct1,
                'noct2' => $noct2,
                'impnoct1' => $impnoct1,
                'impnoct2' => $impnoct2,
                // Compatibilidad con el exportador Excel anterior.
                'impregular' => round((float) ($c['imp_regular'] ?? 0), 2),
                'impirregular' => round((float) ($c['imp_irregular'] ?? 0), 2),
                'impbase' => round((float) ($c['imp_regular'] ?? 0) + (float) ($c['imp_irregular'] ?? 0), 2),
                'impplus' => 0.0,
                'padicionales' => round((float) ($c['padicionales'] ?? 0), 2),
                'impreporte' => round((float) ($c['salario_final'] ?? 0), 2),
                'impbaja' => 0.0, 'implicencia' => 0.0, 'impprestamo' => 0.0,
            ];

            $registros[] = $registro;

            $area = $c['area'] ?? 'Sin área';
            $porArea[$area][] = $registro;

            $totales['salario'] += (float) ($c['salario_escala'] ?? 0);
            $totales['tarifa'] += (float) ($c['tarifa'] ?? 0);
            $totales['cla'] += (float) ($c['cla'] ?? 0);
            $totales['ttotal'] += $ttotal;
            $totales['impescala'] += $impescala;
            $totales['impcla'] += $impcla;
            $totales['impnocturnidad'] += $impnocturnidad;
            $totales['tiempoextra'] += (float) ($c['tiempoextra'] ?? 0);
            $totales['impextra'] += $impextra;
            $totales['impmaestrias'] += $impmaestrias;
            $totales['impsalario2'] += $impsalario2;
        }

        foreach ($totales as $k => $v) {
            $totales[$k] = round((float) $v, 2);
        }

        // Orden del legacy (mostrar_salario_emcarga con 'nombarea'):
        // áreas.order ASC, áreas.nombre ASC, cargos.tarifa DESC.
        usort($registros, function ($a, $b) {
            return ($a['area_orden'] <=> $b['area_orden'])
                ?: strcmp($a['nombarea'], $b['nombarea'])
                ?: (($b['tarifa'] <=> $a['tarifa']));
        });

        return [
            'titulo' => 'DATOS P/NOMINAS SALARIO ADMINISTRATIVO',
            'periodo' => Carbon::createFromDate($ano, $mes, 1)->format('F Y'),
            'registros' => $registros,
            'por_area' => $porArea,
            'totales' => $totales,
        ];
    }

    /**
     * Pago Administrativo por Resultados (formato resultado_adm).
     * Réplica de Reportes.php:4491 pdf_salario_prenomina_resultado_adm +
     * ModSalarioAdmin:973-991 (SISTEMA DE PAGOS POR RESULTADOS EMCARGA HOLGUIN):
     *   impstrt      = impescala + impcla
     *   impsrinicial = impstrt × CDS (tabla cds_entidades, por entidad+mes+año)
     *   penimporte   = % penalización (RESULTADOS ADMINISTRATIVOS, origen 5) sobre impsrinicial
     *   impsrfinal   = impsrinicial − penimporte
     *   imppadicional = impnocturnidad + impmaestrias + impextra
     *   impstotal    = impstrt + impsrfinal + imppadicional
     *
     * @return array{registros: array, totales: array, cds: ?float, tiene_cds: bool}
     */
    public function pagoAdministrativo(int $mes, int $ano, ?int $entidadId = null): array
    {
        $base = $this->prenominaAdministrativo($mes, $ano, $entidadId);
        $cds = \App\Models\CdsEntidad::cdsDe($entidadId, $mes, $ano);

        $idPagoResultadoAdmin = \App\Models\CatalogoItem::query()
            ->where('tipo', 'tipos_pagos_adicionales')
            ->where('origen_id', 5)
            ->value('id');

        $registros = [];
        $totales = array_fill_keys([
            'ttotal', 'impescala', 'impcla', 'impstrt', 'impsrinicial',
            'penimporte', 'impsrfinal', 'impnocturnidad', 'impmaestrias',
            'impextra', 'imppadicional', 'impstotal',
        ], 0.0);

        foreach ($base['registros'] as $r) {
            $impescala = (float) $r['impescala'];
            $impcla = (float) $r['impcla'];
            $impnocturnidad = (float) $r['impnocturnidad'];
            $impmaestrias = (float) $r['impmaestrias'];
            $impextra = (float) $r['impextra'];

            $impstrt = round($impescala + $impcla, 2);
            $impsrinicial = $cds !== null ? round($impstrt * $cds, 2) : 0.0;
            $penimporte = 0.0;

            if ($idPagoResultadoAdmin && $impsrinicial > 0) {
                $penSum = \App\Models\Penalizacion::query()
                    ->where('id_bolsa', $r['id_bolsa'])
                    ->whereYear('fecha', $ano)
                    ->whereMonth('fecha', $mes)
                    ->whereIn('id_tipo_penalizacion', function ($q) use ($idPagoResultadoAdmin) {
                        $q->select('id')->from('tipos_penalizaciones')
                            ->where('tipo_pago_adicional_id', $idPagoResultadoAdmin);
                    })
                    ->sum('importe');

                if ($penSum > 0) {
                    $penimporte = round((min($penSum, 100) * $impsrinicial) / 100, 2);
                }
            }

            $impsrfinal = round($impsrinicial - $penimporte, 2);
            $imppadicional = round($impnocturnidad + $impmaestrias + $impextra, 2);
            $impstotal = $cds !== null ? round($impstrt + $impsrfinal + $imppadicional, 2) : 0.0;

            $registro = $r + [
                'impstrt' => $impstrt,
                'impsrinicial' => $impsrinicial,
                'penimporte' => $penimporte,
                'impsrfinal' => $impsrfinal,
                'imppadicional' => $imppadicional,
                'impstotal' => $impstotal,
            ];
            $registros[] = $registro;

            $totales['ttotal'] += (float) $r['ttotal'];
            $totales['impescala'] += $impescala;
            $totales['impcla'] += $impcla;
            $totales['impstrt'] += $impstrt;
            $totales['impsrinicial'] += $impsrinicial;
            $totales['penimporte'] += $penimporte;
            $totales['impsrfinal'] += $impsrfinal;
            $totales['impnocturnidad'] += $impnocturnidad;
            $totales['impmaestrias'] += $impmaestrias;
            $totales['impextra'] += $impextra;
            $totales['imppadicional'] += $imppadicional;
            $totales['impstotal'] += $impstotal;
        }

        foreach ($totales as $k => $v) {
            $totales[$k] = round((float) $v, 2);
        }

        return [
            'registros' => $registros,
            'totales' => $totales,
            'cds' => $cds,
            'tiene_cds' => $cds !== null,
        ];
    }

    /**
     * Modelo 1 — Control diario de las transportaciones por chofer.
     *
     * Réplica 1:1 del legado `ModSalarioChofer::mostrar_modelo1` (que alimenta
     * `Reportes.php:1872 npdf_salario_choferes_modelo1_transcar`). El corte
     * temporal es por `aforos.fecha_parte` (fparte), y por chofer se listan las
     * 16 columnas: fechas inicio/terminación, equipo (capacidad), HR, CP, KMS
     * (carga), TNS, tiempos (otros/mov/carga/descarga/total), escala, tarifa
     * CLA, valor CLA, salario x TRT, ingresos, tasa, fondo formado y nota
     * (doble chofer / feriado / almacenaje / vacaciones).
     */
    public function modelo1(int $mes, int $ano, ?int $idBolsa = null): array
    {
        $entidadId = (int) session('entidad_activa_id') ?: null;
        $ids = $entidadId ? \App\Models\Entidad::idsPermitidos($entidadId) : [];

        // Parámetros CLA por provincia (legacy: idprovincias != 3200 → 1.20/2.30;
        // == 3200 (La Habana) → 0.98/1.88).
        $entidadActiva = $entidadId ? \App\Models\Entidad::find($entidadId) : null;
        $esHabana = $entidadActiva && (int) $entidadActiva->id_provincia === 3200;
        $varcla90 = $esHabana ? 0.98 : 1.20;
        $varcla91 = $esHabana ? 1.88 : 2.30;
        $almacenaje = $entidadActiva ? (float) $entidadActiva->almacenaje : 0.0;

        $query = Aforo::query()
            ->whereYear('fecha_parte', $ano)
            ->whereMonth('fecha_parte', $mes)
            ->whereHas('cartaPorte', fn ($cp) => $cp->where('cancelada', false))
            ->with([
                'cartaPorte:id,cancelada,numero,id_hoja_ruta,id_chofer,id_chofer2,distancia',
                'cartaPorte.hojaRuta:id,numero,id_tractivo,id_arrastre,id_chofer,id_chofer2',
                'cartaPorte.hojaRuta.tractivo:id,codigo,capacidad_toneladas',
                'cartaPorte.chofer:id,nombre,apellidos,id_cargo',
                'cartaPorte.chofer.cargo:id,nombre,tarifa,cla',
                'tasa:id,nombre,tasa,tasa2',
            ]);

        if ($ids) {
            $query->whereHas('cartaPorte.hojaRuta.tractivo', fn ($t) => $t->whereIn('tractivos.id_entidad', $ids));
        }

        if ($idBolsa) {
            $query->where(function ($q) use ($idBolsa) {
                $q->whereHas('cartaPorte', fn ($cp) => $cp->where('id_chofer', $idBolsa))
                  ->orWhereHas('cartaPorte', fn ($cp) => $cp->where('id_chofer2', $idBolsa));
            });
        }

        $aforos = $query->orderBy('fecha_parte')
            ->orderBy('id_carta_porte')
            ->get();

        // Agrupar por chofer (id_chofer de la carta de porte).
        $porChofer = [];
        foreach ($aforos as $aforo) {
            $cp = $aforo->cartaPorte;
            if (!$cp) {
                continue;
            }

            $hr = $cp->hojaRuta;
            $choferId = $cp->id_chofer;
            $chofer = $cp->chofer;
            $nombreChofer = $chofer ? trim($chofer->nombre . ' ' . $chofer->apellidos) : 'DESCONOCIDO';

            if (!isset($porChofer[$choferId])) {
                $bolsa = Bolsa::find($choferId);
                $porChofer[$choferId] = [
                    'id_bolsa' => $choferId,
                    'nombre' => $nombreChofer,
                    'carnet' => $bolsa?->ci ?? '',
                    'cargo' => $chofer?->cargo?->nombre ?? '',
                    'tarifa' => (float) ($chofer?->cargo?->tarifa ?? 0),
                    'registros' => [],
                    'totales' => [
                        'nro_cp' => 0, 'kms' => 0, 'tons' => 0,
                        'tperm' => 0, 'tmov' => 0, 'tcarga' => 0, 'tdescarga' => 0, 'ttotal' => 0,
                        'saltrt' => 0, 'impcla' => 0, 'saltrtcla' => 0,
                        'ingresos' => 0, 'salario' => 0,
                    ],
                ];
            }

            $kmcarga = (float) ($aforo->km_carga_total ?? 0);
            $tnreal = (float) ($aforo->tn_real_total ?? 0);
            $tperm = (float) ($aforo->tiempo_otros ?? 0);
            $tmov = (float) ($aforo->tiempo_movimiento ?? 0);
            $tcarga = (float) ($aforo->tiempo_carga ?? 0);
            $tdescarga = (float) ($aforo->tiempo_descarga ?? 0);
            $ttotal = (float) ($aforo->tiempo_total ?? 0);
            $ingresomt = (float) ($aforo->ingreso_mt ?? 0);
            $tasa = (float) ($aforo->tasa ?? 0);
            $tasa2 = (float) ($aforo->tasa?->tasa2 ?? 0);
            $almflete = (float) ($aforo->almacenaje_flete ?? 0);
            $tarifa = (float) ($chofer?->cargo?->tarifa ?? 0);

            // Ajustes legacy: doble chofer, almacenaje, vacaciones, feriado.
            $ajuste = 0;
            $ingreso = $ingresomt;
            $salalm = 0.0;
            $esDobleChofer = $cp->id_chofer2 > 0 && $cp->id_chofer2 != $cp->id_chofer;

            if ($esDobleChofer) {
                $ajuste = 1;
                if ($kmcarga <= 250) {
                    $tnreal = round($tnreal / 2, 2);
                }
                if ($almflete > 0) {
                    $ingreso = round($ingresomt - $almflete, 2);
                    $salalm = round((($almflete / 2) * $almacenaje), 2);
                } else {
                    $ingreso = round($ingresomt / 2, 2);
                }
                if ($tasa2 > 0) {
                    $tasa = $tasa2;
                }
                $ingresomt = round($ingresomt / 2, 2);
            } else {
                $ingreso = $ingresomt;
                if ($almflete > 0) {
                    $ingreso = round($ingresomt - $almflete, 2);
                    $salalm = round($almflete * $almacenaje, 2);
                }
            }
            $salario = round(($ingreso * $tasa) + $salalm, 2);
            if ($almflete > 0) {
                $ajuste = 5;
            }

            // CLA según kmcarga (escala).
            if ($kmcarga <= 90) {
                $tarcla = $varcla90;
            } else {
                $tarcla = $varcla91;
            }
            $impcla = round($ttotal * $tarcla, 2);
            $saltrt = round($ttotal * $tarifa, 2);
            $saltrtcla = round($saltrt + $impcla, 2);

            $registro = [
                'fcarga' => $aforo->fecha_carga?->format('m-d') ?? '',
                'hcarga1' => $aforo->hora_carga_1 ?? '',
                'fdescarga' => $aforo->fecha_descarga?->format('m-d') ?? '',
                'hdescarga1' => $aforo->hora_descarga_1 ?? '',
                'equipo' => $hr?->tractivo?->codigo ?? '',
                'capacidad' => (int) round((float) ($hr?->tractivo?->capacidad_toneladas ?? 0), 0),
                'nro_hr' => $hr?->numero ?? '',
                'nro_cp' => $cp->numero ?? '',
                'kmcarga' => $kmcarga,
                'tnreal' => $tnreal,
                'tperm' => $tperm,
                'tmov' => $tmov,
                'tcarga' => $tcarga,
                'tdescarga' => $tdescarga,
                'ttotal' => $ttotal,
                'saltrt' => $saltrt,
                'tarcla' => $tarcla,
                'impcla' => $impcla,
                'saltrtcla' => $saltrtcla,
                'ingresos' => round($ingresomt, 2),
                'tasa' => round($tasa, 5),
                'salario' => $salario,
                'ajuste' => $ajuste,
            ];

            $porChofer[$choferId]['registros'][] = $registro;

            $t = &$porChofer[$choferId]['totales'];
            $t['nro_cp']++;
            // Paridad legacy: la columna KMS muestra kmcarga, pero el TOTAL
            // del modelo suma `distancia` (com_girado.distancia).
            $t['kms'] += (float) ($cp->distancia ?? 0);
            $t['tons'] += $tnreal;
            $t['tperm'] += $tperm;
            $t['tmov'] += $tmov;
            $t['tcarga'] += $tcarga;
            $t['tdescarga'] += $tdescarga;
            $t['ttotal'] += $ttotal;
            $t['saltrt'] += $saltrt;
            $t['impcla'] += $impcla;
            $t['saltrtcla'] += $saltrtcla;
            $t['ingresos'] += $registro['ingresos'];
            $t['salario'] += $salario;
        }

        // Ordenar por nombre
        uasort($porChofer, fn ($a, $b) => strcmp($a['nombre'], $b['nombre']));

        foreach ($porChofer as $k => $v) {
            foreach ($v['totales'] as $fk => $fv) {
                $porChofer[$k]['totales'][$fk] = round($fv, 2);
            }
        }

        return [
            'titulo' => 'MODELO 1 - CONTROL DIARIO DE LAS TRANSPORTACIONES',
            'periodo' => Carbon::createFromDate($ano, $mes, 1)->format('F Y'),
            'por_chofer' => array_values($porChofer),
        ];
    }

    private function entidadActivaId(): ?int
    {
        return (int) session('entidad_activa_id') ?: null;
    }

    /**
     * Incidencias de un tipo para el reporte "PRENOMINA INCIDENCIAS AL TIEMPO TRABAJADO".
     * Réplica de ModIncidencias::mostrar_detalle (ModIncidencias.php:216), que filtra
     * por tipo de incidencia (origen_id del catálogo `tipos_incidencias`), mes y año de
     * operaciones, agrupando por área → trabajador. La columna IMPORTE se rellena con
     * `periodo_actual` (el legacy tenía `importe=0`; la referencia muestra `pactual`).
     *
     * @return array{registros: array, total_general: float, titulo_tipo: string}
     */
    public function incidenciasTipo(int $mes, int $ano, int $origenId, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);

        $tipo = \App\Models\CatalogoItem::query()
            ->where('tipo', 'tipos_incidencias')
            ->where('origen_id', $origenId)
            ->first();

        $tipoNombre = $tipo?->nombre ?? '';

        $idsBolsas = Bolsa::query()
            ->when(! empty($entidadesPermitidas),
                fn ($q) => $q->whereIn('id_entidad', $entidadesPermitidas),
                fn ($q) => $q->whereRaw('1 = 0'))
            ->pluck('id');

        $idTipoCatalogo = $tipo?->id;
        if (! $idTipoCatalogo || $idsBolsas->isEmpty()) {
            return ['registros' => [], 'total_general' => 0.0, 'titulo_tipo' => $tipoNombre];
        }

        $incidencias = Incidencia::query()
            ->whereIn('id_bolsa', $idsBolsas)
            ->where('id_tipo_incidencia', $idTipoCatalogo)
            ->whereYear('fecha_inicio', $ano)
            ->whereMonth('fecha_inicio', $mes)
            ->with(['bolsa:id,nombre,apellidos,ci,versat,id_entidad,id_area',
                    'bolsa.area:id,nombre,orden'])
            ->get();

        $registros = [];
        $totalGeneral = 0.0;

        foreach ($incidencias as $inc) {
            $bolsa = $inc->bolsa;
            $pactual = (float) $inc->periodo_actual;
            $importe = (float) $inc->importe;
            // El legacy puso `importe` en IMPORTE pero era 0; la referencia muestra `pactual`.
            $importeMostrar = $importe > 0 ? $importe : $pactual;
            $totalGeneral += $importeMostrar;

            $registros[] = [
                'nronomina' => $bolsa ? ($this->nroci == 1 ? ($bolsa->ci ?? '') : ($bolsa->versat ?? $bolsa->ci ?? '')) : '',
                'nombrecompleto' => $bolsa ? ($bolsa->nombre.' '.$bolsa->apellidos) : '',
                'cidentidad' => $bolsa?->ci ?? '',
                'nombarea' => $bolsa?->area?->nombre ?? 'Sin área',
                'area_orden' => (int) ($bolsa?->area?->orden ?? 0),
                'clave' => $tipoNombre,
                'inicio' => $inc->fecha_inicio?->format('Y-m-d') ?? '',
                'final' => $inc->fecha_fin?->format('Y-m-d') ?? '',
                'pactual' => $pactual,
                'importe' => $importeMostrar,
            ];
        }

        // Orden del legacy: área (order), área nombre, trabajador nombre.
        usort($registros, function ($a, $b) {
            return ($a['area_orden'] <=> $b['area_orden'])
                ?: strcmp($a['nombarea'], $b['nombarea'])
                ?: strcmp($a['nombrecompleto'], $b['nombrecompleto']);
        });

        return [
            'registros' => $registros,
            'total_general' => round($totalGeneral, 2),
            'titulo_tipo' => $tipoNombre,
        ];
    }

    private function entidadesPermitidas(?int $entidadId = null): array
    {
        $id = $entidadId ?? $this->entidadActivaId();
        if (!$id) return [];

        return \App\Models\Entidad::idsPermitidos((int) $id);
    }

    /**
     * Resumen de los tiempos de los choferes (reporte 8 del plan RRHH).
     * Réplica de npdf_salario_choferes_tiempos (Reportes.php:2939) +
     * ModSalarioAdmin::mostrar_salario_emcarga(sistema=2, orden nronomina).
     *
     * DISTRIBUCION DE LOS TIEMPOS: MTD/MOV/CARGA/DESCA/TOTAL del modelo1.
     * DESCUENTOS por incidencias (claves legacy): VAC(6), SUB(12,20),
     * REUB(43), RECALIF(48), INT/GARANTIA(3,44,46), FNT/RECESO(180),
     * OTROS(resto) y TOTAL(tincidencias). Solo choferes con ttotal > 0.
     *
     * @return array{registros: array, totales: array}
     */
    public function tiemposChoferes(int $mes, int $ano, ?int $entidadId = null): array
    {
        $modelo1 = $this->modelo1($mes, $ano);
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);

        $registros = [];
        $totales = array_fill_keys(
            ['tperm', 'tmov', 'tcarga', 'tdescarga', 'ttotal', 'vacaciones', 'subsidios',
                'reubicado', 'recalificacion', 'tgarantia', 'receso', 'totros', 'tincidencias'],
            0.0
        );

        foreach ($modelo1['por_chofer'] as $ch) {
            $bolsa = \App\Models\Bolsa::find($ch['id_bolsa'] ?? null);
            if (!$bolsa || $bolsa->id_entidad === null) {
                continue;
            }
            if (! empty($entidadesPermitidas) && ! in_array((int) $bolsa->id_entidad, $entidadesPermitidas, true)) {
                continue;
            }

            $t = $ch['totales'] ?? [];
            $ttotal = (float) ($t['ttotal'] ?? 0);
            if ($ttotal <= 0) {
                continue;
            }

            // Descuentos por incidencia del mes (claves legacy).
            $vac = $sub = $reub = $recal = $gar = $receso = $otros = 0.0;
            $incidencias = Incidencia::query()
                ->where('id_bolsa', $bolsa->id)
                ->whereYear('fecha_inicio', $ano)
                ->whereMonth('fecha_inicio', $mes)
                ->with('tipoIncidencia')
                ->get();

            foreach ($incidencias as $inc) {
                $extra = $inc->tipoIncidencia?->extra ?? [];
                $clave = (string) ($extra['clave'] ?? ($inc->tipoIncidencia?->origen_id ?? $inc->id_tipo_incidencia));
                $tiempo = (float) $inc->periodo_actual;

                match (true) {
                    $clave === '6' => $vac += $tiempo,
                    in_array($clave, ['12', '20'], true) => $sub += $tiempo,
                    $clave === '43' => $reub += $tiempo,
                    $clave === '48' => $recal += $tiempo,
                    in_array($clave, ['3', '44', '46'], true) => $gar += $tiempo,
                    $clave === '180' => $receso += $tiempo,
                    default => $otros += $tiempo,
                };
            }
            $tincidencias = round($vac + $sub + $reub + $recal + $gar + $receso + $otros, 2);

            $registro = [
                'id_bolsa' => $bolsa->id,
                'versat' => $bolsa->versat ?? '',
                'nombrecompleto' => $bolsa->nombrecompleto,
                'tperm' => (float) ($t['tperm'] ?? 0),
                'tmov' => (float) ($t['tmov'] ?? 0),
                'tcarga' => (float) ($t['tcarga'] ?? 0),
                'tdescarga' => (float) ($t['tdescarga'] ?? 0),
                'ttotal' => $ttotal,
                'vacaciones' => round($vac, 2),
                'subsidios' => round($sub, 2),
                'reubicado' => round($reub, 2),
                'recalificacion' => round($recal, 2),
                'tgarantia' => round($gar, 2),
                'receso' => round($receso, 2),
                'totros' => round($otros, 2),
                'tincidencias' => $tincidencias,
            ];
            $registros[] = $registro;

            foreach ($totales as $k => $_) {
                $totales[$k] += (float) ($registro[$k] ?? 0);
            }
        }

        // Orden por versat (el legacy ordena por rh_movimientos.nronomina = versat).
        usort($registros, fn ($a, $b) => strcmp((string) $a['versat'], (string) $b['versat']));

        foreach ($totales as $k => $v) {
            $totales[$k] = round((float) $v, 2);
        }

        return ['registros' => $registros, 'totales' => $totales];
    }
}
