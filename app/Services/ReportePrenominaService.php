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
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov')
                ->whereHas('plantilla.area', fn ($a) => $a->where('id_tipo_sistema_pago', 2)))
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->when(! empty($entidadesPermitidas),
                fn ($q) => $q->whereIn('id_entidad', $entidadesPermitidas),
                fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['movimientoVigente.plantilla.cargo:id,nombre,tarifa,cla', 'movimientoVigente.plantilla.area:id,nombre,orden'])
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

            $tarifa = (float) ($chofer->cargoActual()?->tarifa ?? 0);

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
                'nombcargo' => $chofer->cargoActual()?->nombre ?? '',
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
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov')
                ->whereHas('plantilla.area', fn ($a) => $a->where('id_tipo_sistema_pago', 1)))
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
                'cartaPorte.chofer:id,nombre,apellidos',
                
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

            // Paridad legacy: `mostrar_modelo1` consulta por chofer con
            // `having idchofer = X OR idchofer2 = X`, de modo que las cartas de
            // porte de doble chofer se contabilizan para AMBOS choferes (cada
            // uno acumula sus propios tiempos del mismo aforo). Aquí un aforo
            // puede therefore alimentar dos grupos: id_chofer e id_chofer2.
            $choferesCp = [];
            if ($cp->id_chofer) {
                $choferesCp[$cp->id_chofer] = true;
            }
            if ($cp->id_chofer2 && $cp->id_chofer2 != $cp->id_chofer) {
                $choferesCp[$cp->id_chofer2] = true;
            }
            if (empty($choferesCp)) {
                continue;
            }

            foreach (array_keys($choferesCp) as $choferId) {
            $chofer = $cp->id_chofer == $choferId ? $cp->chofer : Bolsa::with('cargo')->find($choferId);
            $nombreChofer = $chofer ? trim($chofer->nombre . ' ' . $chofer->apellidos) : 'DESCONOCIDO';

            if (!isset($porChofer[$choferId])) {
                $bolsa = $chofer ?? Bolsa::find($choferId);
                $porChofer[$choferId] = [
                    'id_bolsa' => $choferId,
                    'nombre' => $nombreChofer,
                    'carnet' => $bolsa?->ci ?? '',
                    'cargo' => $chofer?->cargoActual()?->nombre ?? '',
                    'tarifa' => (float) ($chofer?->cargoActual()?->tarifa ?? 0),
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
            $tarifa = (float) ($chofer?->cargoActual()?->tarifa ?? 0);

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

            // Paridad legacy (ModSalarioChofer::671/775): el tiempo total del
            // chofer nunca supera 240 horas; el exceso pasa a tiempo irregular.
            $ttotalOriginal = $ttotal;
            if ($ttotal > 240) {
                $ttotal = 240.0;
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
                // Paridad legacy mostrar_modelo1: regular = ttotal (cap 240),
                // irregular = exceso sobre 240.
                'regular' => $ttotal,
                'irregular' => round(max(0.0, $ttotalOriginal - 240), 2),
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

        // Orden alfabético por nombre (la referencia del reporte 8 sale por
        // rh_bolsa.nombrecompleto; pdftotext lo confirma fila a fila).
        usort($registros, fn ($a, $b) => strcmp($a['nombrecompleto'], $b['nombrecompleto']));

        foreach ($totales as $k => $v) {
            $totales[$k] = round((float) $v, 2);
        }

        return ['registros' => $registros, 'totales' => $totales];
    }

    /**
     * MODELO ANALISIS DEL SALARIO TRANSPORTACION (reporte 7 del plan RRHH).
     * Réplica de npdf_salario_choferes_resumen_analisis (Reportes.php:3068) +
     * ModSalarioAdmin::mostrar_salario_emcarga (sistema de pago = 2, líneas
     * 449-617): por chofer con ttotal > 0 o tgarantia > 0.
     *
     * Columnas: NOMBRE, INGRESOS, TIEMPO TRANSPOR (ttotal), GARANTIA (tgarantia),
     * TOTAL (ttotal), COEFICIENTE (impsalfinal - impferiados), TIEMPO TRABAJADO
     * (impbase2), RESULTADO (impresultado), TOTAL (impsalfinal), GARANTIA
     * (impgarantia), NORMA SALARIAL, TARIFA TRANSPORTA (normatransp) y TARIFA
     * GENERAL (normatotal).
     *
     * El legacy acumula las garantías e importes de incidencia con las claves:
     * vacaciones(6), garantía/interrupción(3,44,46), feriados(21).
     */
    public function analisisTransportacion(int $mes, int $ano, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);

        $choferes = Bolsa::query()
            ->where('activo', true)
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov')
                ->whereHas('plantilla.area', fn ($a) => $a->where('id_tipo_sistema_pago', 2)))
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->when(! empty($entidadesPermitidas),
                fn ($q) => $q->whereIn('id_entidad', $entidadesPermitidas),
                fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['movimientoVigente.plantilla.cargo:id,nombre,tarifa,cla', 'movimientoVigente.plantilla.area:id,nombre,orden'])
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get();

        $registros = [];
        $totales = array_fill_keys(
            ['ingresos', 'ttotal', 'tgarantia', 'coeficiente', 'tiempo_trabajado',
                'resultado', 'total', 'garantia'], 0.0
        );

        foreach ($choferes as $chofer) {
            $res = $this->choferCalc->calcularSalarioChofer($chofer->id, $mes, $ano);
            if (! $res) {
                continue;
            }

            $tarifa = (float) ($chofer->cargoActual()?->tarifa ?? 0);
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

            // Incremento salarial inicial (resultado antes de penalizar).
            $impiresultado = round($tsalario - $impbase2, 2);
            if ($impiresultado < 0) {
                $impiresultado = 0;
            }

            // Penalización por resultado (pago adicional 6 = RESULTADO CHOFERES).
            $penimporte = 0.0;
            $idPagoResultadoChoferes = DB::table('catalogo_items')
                ->where('tipo', 'tipos_pagos_adicionales')
                ->where('origen_id', 6)
                ->value('id');
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
                    $penimporte = round((min($penImporteSum, 100) * $impiresultado) / 100, 2);
                }
            }
            $impresultado = max(0, round($impiresultado - $penimporte, 2));

            // Incidencias del mes: garantías (3,44,46), feriados (21) e importes.
            $tgarantia = $impgarantia = $impferiados = $impincidencia = 0.0;
            $incidencias = Incidencia::query()
                ->where('id_bolsa', $chofer->id)
                ->whereYear('fecha_inicio', $ano)
                ->whereMonth('fecha_inicio', $mes)
                ->with('tipoIncidencia')
                ->get();

            foreach ($incidencias as $inc) {
                $extra = $inc->tipoIncidencia?->extra ?? [];
                $clave = (string) ($extra['clave'] ?? ($inc->tipoIncidencia?->origen_id ?? $inc->id_tipo_incidencia));
                $tiempo = (float) $inc->periodo_actual;
                $importe = (float) $inc->importe;

                if (in_array($clave, ['3', '44', '46'], true)) {
                    $tgarantia += $tiempo;
                    $impgarantia += $importe;
                }
                if ($clave === '21') {
                    $impferiados += $importe;
                }
                // impsuma > 0 (tipos que suman importe a la prenomina).
                $impsuma = (int) ($extra['impsuma'] ?? 0);
                if ($impsuma > 0) {
                    $impincidencia += $importe;
                }
            }
            $impgarantia = round($impgarantia, 2);
            $impferiados = round($impferiados, 2);
            $impincidencia = round($impincidencia, 2);

            // Salario final (ModSalarioAdmin:590-597).
            $impsalfinal = round($impbase2 + $impferiados + $impgarantia + $impincidencia + $impresultado, 2);

            // Normas (solo si hay valores positivos — el legacy deja '' si no).
            $normasalarial = ($impsalfinal > 0 && $impbase > 0)
                ? round($impsalfinal / $impbase, 2) : 0.0;
            $normatransp = ($impsalfinal > 0 && $ttotal > 0)
                ? round($impsalfinal / $ttotal, 2) : 0.0;
            $normatotal = $normatransp;

            // El reporte solo incluye choferes con tiempo o garantía.
            if ($ttotal <= 0 && $tgarantia <= 0) {
                continue;
            }

            $registro = [
                'id_bolsa' => $chofer->id,
                'versat' => $chofer->versat ?? '',
                'nombrecompleto' => $chofer->nombrecompleto,
                'ingresos' => $ingresos,
                'ttotal' => $ttotal,
                'tgarantia' => round($tgarantia, 2),
                'coeficiente' => round($impsalfinal - $impferiados, 2),
                'tiempo_trabajado' => $impbase2,
                'resultado' => $impresultado,
                'total' => $impsalfinal,
                'garantia' => $impgarantia,
                'normasalarial' => $normasalarial,
                'normatransp' => $normatransp,
                'normatotal' => $normatotal,
            ];
            $registros[] = $registro;

            $totales['ingresos'] += $ingresos;
            $totales['ttotal'] += $ttotal;
            $totales['tgarantia'] += $registro['tgarantia'];
            $totales['coeficiente'] += $registro['coeficiente'];
            $totales['tiempo_trabajado'] += $impbase2;
            $totales['resultado'] += $impresultado;
            $totales['total'] += $impsalfinal;
            $totales['garantia'] += $impgarantia;
        }

        // Orden alfabético (la referencia sale por rh_bolsa.nombrecompleto).
        usort($registros, fn ($a, $b) => strcmp($a['nombrecompleto'], $b['nombrecompleto']));

        foreach ($totales as $k => $v) {
            $totales[$k] = round((float) $v, 2);
        }

        // Normas de los TOTALES (Reportes.php:3176-3177). OJO: el legacy
        // acumula $ttotal DOS veces por empleado (líneas 3157+3159), de modo
        // que TRANSPOR/TOTAL de la fila TOTALES y los divisores de las normas
        // usan el DOBLE de la suma (2×2,374.31 = 4,748.62 en la referencia).
        $ttotalDoble = round($totales['ttotal'] * 2, 2);
        $totales['ttotal'] = $ttotalDoble;
        $totales['normasalarial'] = $totales['tiempo_trabajado'] > 0
            ? round($totales['coeficiente'] / $totales['tiempo_trabajado'], 2) : 0.0;
        $totales['normatransp'] = $ttotalDoble > 0
            ? round($totales['coeficiente'] / $ttotalDoble, 2) : 0.0;
        $totales['normatotal'] = $ttotalDoble > 0
            ? round($totales['total'] / $ttotalDoble, 2) : 0.0;

        return ['registros' => $registros, 'totales' => $totales];
    }

    /**
     * Días feriados del mes (legacy rh_meses.dias, ';' separado).
     */
    private function feriadosMesLegacy(int $mes): array
    {
        $record = DB::connection('legacy')->table('rh_meses')->where('idmes', $mes)->first();

        return (!$record || empty($record->dias)) ? [] : array_map('intval', explode(';', $record->dias));
    }

    /**
     * Días laborables del mes (legacy rh_meses.dlaborables / dlab2).
     */
    private function diasLaborablesLegacy(int $mes, int $opcion = 1): float
    {
        $record = DB::connection('legacy')->table('rh_meses')->where('idmes', $mes)->first();
        if (!$record) {
            return 0;
        }

        return (float) ($opcion == 1 ? $record->dlaborables : $record->dlab2);
    }

    /**
     * Tiempo del mes (legacy tiempo_mes, ModSalarioAdmin:141-166).
     * tiempo1 = (resto*9)+(viernes*8) donde resto = dias-(dom+sab+vie).
     */
    private function tiempoMesLegacy(int $mes, int $ano): float
    {
        $dias = Carbon::createFromDate($ano, $mes, 1)->daysInMonth;
        $dom = $sab = $vie = 0;
        for ($i = 1; $i <= $dias; $i++) {
            $w = (int) date('w', mktime(0, 0, 0, $mes, $i, $ano));
            if ($w === 0) $dom++;
            if ($w === 6) $sab++;
            if ($w === 5) $vie++;
        }
        $resto = $dias - ($dom + $sab + $vie);

        return round(($resto * 9) + ($vie * 8), 2);
    }

    /**
     * Descuentos por incidencia del mes (legacy ModIncidencias::mostrar_mes,
     * claves legacy = origen_id del catálogo). Devuelve por incidencia:
     * clave, inicio(día), final(día), tiempo (periodo_actual), tsuma, impsuma.
     */
    private function incidenciasMes(int $idBolsa, int $mes, int $ano): array
    {
        return Incidencia::query()
            ->where('id_bolsa', $idBolsa)
            ->whereYear('fecha_inicio', $ano)
            ->whereMonth('fecha_inicio', $mes)
            ->with('tipoIncidencia')
            ->get()
            ->map(function (Incidencia $inc) {
                $extra = $inc->tipoIncidencia?->extra ?? [];

                return [
                    'clave' => (string) ($extra['clave'] ?? ($inc->tipoIncidencia?->origen_id ?? '')),
                    'inicio' => (int) $inc->fecha_inicio?->format('j'),
                    'final' => (int) $inc->fecha_fin?->format('j'),
                    'tiempo' => (float) $inc->periodo_actual,
                    'tsuma' => (int) ($extra['tsuma'] ?? 0),
                    'impsuma' => (int) ($extra['impsuma'] ?? 0),
                ];
            })
            ->all();
    }

    /**
     * Tiempo irregular del mes por trabajador (legacy rh_saladmin.irregular).
     */
    private function irregularMovimiento(int $idMovimientoRrhh, int $mes, int $ano): float
    {
        // rh_saladmin se referencia por idmovimientos legacy; el nuevo
        // movimientos_rrhh guarda id_legacy para tractivos, no para RRHH.
        // El ETL de nómina no migró rh_saladmin: se consulta directo al legacy
        // vía idbolsa→rh_movimientos activo (paridad mostrar_irregular).
        $idLegacy = DB::connection('legacy')->table('rh_movimientos')
            ->join('rh_bolsa', 'rh_movimientos.idbolsa', '=', 'rh_bolsa.idbolsa')
            ->where('rh_movimientos.idmovimientos', $idMovimientoRrhh)
            ->value('rh_movimientos.idmovimientos');

        if (!$idLegacy) {
            $idLegacy = $idMovimientoRrhh;
        }

        $irregular = DB::connection('legacy')->table('rh_saladmin')
            ->where('idmovimientos', $idLegacy)
            ->whereYear('fsaladmin', $ano)
            ->whereMonth('fsaladmin', $mes)
            ->sum('irregular');

        return (float) $irregular;
    }

    /**
     * Mapa de claves legacy → columnas de descuento del SC-4-05
     * (réplica del switch de ModMovimientos::mostrar_control).
     */
    private const MAPA_DESCUENTOS_SC405 = [
        '1' => 'cm', '2' => 'cm', '4' => 'cr', '6' => 'vac', '7' => 'lss',
        '8' => 'chm', '9' => 'chm', '10' => 'lm', '11' => 'lm', '12' => 'ai',
        '14' => 'at', '15' => 'fnt', '16' => 'ai', '18' => 'mov', '26' => 'ab',
        '29' => 'mov', '33' => 'imp', '34' => 'imp', '35' => 'imp', '36' => 'pm',
        '38' => 'sl', '43' => 'rt', '46' => 'int', '48' => 'cr', '49' => 'fnt',
        '51' => 'rt', '52' => 'tadrl',
    ];

    /**
     * SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO (reporte 10) — administrativo.
     * Réplica de ModMovimientos::mostrar_control (sistema de pago != 2).
     *
     * Por trabajador: nronomina, nombrecompleto, categoría (1ra letra),
     * tiempo por día (8/4/D o turnos si grupo horario 3; claves de incidencia
     * encima), descuentos agregados por clave legacy, noct1/noct2 y regular.
     */
    public function controlDiario(int $mes, int $ano, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);
        $dias = Carbon::createFromDate($ano, $mes, 1)->daysInMonth;
        $tiempom = $this->tiempoMesLegacy($mes, $ano);
        $dialab = $this->diasLaborablesLegacy($mes, 1);
        $dialab2 = $this->diasLaborablesLegacy($mes, 2);

        $trabajadores = Bolsa::query()
            ->where('activo', true)
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov')
                ->whereHas('plantilla.area', fn ($a) => $a->where('id_tipo_sistema_pago', '!=', 2)))
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->when(! empty($entidadesPermitidas),
                fn ($q) => $q->whereIn('id_entidad', $entidadesPermitidas),
                fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['cargo:id,nombre,tarifa,en_salario,id_fondo_tiempo,id_grupo_horario,id_grupo_escala,id_categoria_cargo',
                    'cargo.fondo_tiempo:id,fondo_tiempo',
                    'cargo.categoria_cargo:id,nombre',
                    'cargo.grupo_escala:id,nombre',
                    'area:id,nombre,orden'])
            ->orderBy('id_area')
            ->get();

        // Orden legacy: rh_areas.order + grupo escala DESC + nombcatcargo… el
        // ETL no trae grupo escala en área; se ordena por área (orden) y luego
        // por nombre (heurística estable, misma que la referencia muestra por
        // bloques de área).
        $registros = [];
        foreach ($trabajadores as $trabajador) {
            $movimiento = $trabajador->movimientosRrhh()
                ->whereNull('fbaja')->where('origen', 'mov')
                ->first();

            $cargo = $trabajador->cargoActual();
            $fondoTiempo = (float) ($cargo?->fondo_tiempo?->fondo_tiempo ?? 0);
            $enSalario = (int) ($cargo?->en_salario ?? 0);
            $tarifa = (float) ($cargo?->tarifa ?? 0);
            $catCargo = $cargo?->categoria_cargo?->nombre ?? '';
            $grupoHorario = (int) ($cargo?->id_grupo_horario ?? 0);

            // Tiempo regular según categoría (legacy 509-524).
            if ($enSalario == 1) {
                $regular1 = 24.0;
            } elseif ($catCargo === 'OBRERO' || $catCargo === 'OPERARIO') {
                $regular1 = $tiempom;
            } else {
                $regular1 = round($fondoTiempo * 24, 2);
            }
            if ($regular1 == 216.0) { $regular1 = $tiempom; }
            if ($regular1 == 190.6) { $regular1 = round($dialab * 8, 2); }
            if ($regular1 == 173.3) { $regular1 = round($dialab2 * 8, 2); }

            $tirregular = $movimiento ? $this->irregularMovimiento($movimiento->id, $mes, $ano) : 0.0;

            // Tiempo por día.
            $tiempo = array_fill(0, $dias, '');
            $noct1 = $noct2 = 0.0;
            if ($grupoHorario === 3) {
                $turnos = $movimiento
                    ? \App\Models\Turno::where('id_movimiento_rrhh', $movimiento->id)
                        ->whereYear('inicio', $ano)->whereMonth('inicio', $mes)
                        ->orderBy('inicio')->get()
                    : collect();
                foreach ($turnos as $turno) {
                    for ($a = (int) $turno->inicio?->format('j'); $a <= (int) $turno->final?->format('j'); $a++) {
                        if ($a >= 1 && $a <= $dias) {
                            $tiempo[$a - 1] = round((float) $turno->tiempo, 2);
                        }
                    }
                    $noct1 += (float) $turno->noct1;
                    $noct2 += (float) $turno->noct2;
                }
                $regular = $regular1;
            } else {
                for ($i = 0; $i < $dias; $i++) {
                    $tiempo[$i] = 8;
                    $w = (int) date('w', mktime(0, 0, 0, $mes, $i + 1, $ano));
                    if ($w === 0) $tiempo[$i] = 'D';
                    if ($w === 6) $tiempo[$i] = 4;
                }
            }

            // Descuentos por incidencia.
            $d = array_fill_keys(['vac','cr','ai','lm','cm','lss','mov','int','fnt','ab','chm','rt','at','pm','sl','imp','o','tadrl','tiempo1','tiempo2'], 0.0);
            foreach ($this->incidenciasMes($trabajador->id, $mes, $ano) as $inc) {
                for ($a = $inc['inicio']; $a <= $inc['final']; $a++) {
                    if ($a >= 1 && $a <= $dias) {
                        $tiempo[$a - 1] = $inc['clave'];
                    }
                }
                $col = self::MAPA_DESCUENTOS_SC405[$inc['clave']] ?? 'o';
                $d[$col] += $inc['tiempo'];
                $d['tiempo1'] += $inc['tiempo'];
                $d['tiempo2'] += ($inc['tsuma'] > 0) ? $inc['tiempo'] : -$inc['tiempo'];
            }
            $d['tiempo1'] = round($d['tiempo1'] - $d['tadrl'], 2);
            $d['noct1'] = round($noct1, 2);
            $d['noct2'] = round($noct2, 2);

            if ($grupoHorario === 3) {
                $regular = $regular1;
            } else {
                $regular = round($regular1 - $tirregular + $d['tiempo2'] + $d['tadrl'], 2);
            }
            if ($regular < 0) {
                $regular = 0.0;
            }

            $registros[] = [
                'id_bolsa' => $trabajador->id,
                'nronomina' => $movimiento?->nronomina ?? $trabajador->versat ?? '',
                'nombrecompleto' => $trabajador->nombrecompleto,
                'nombarea' => $trabajador->area?->nombre ?? '',
                'area_orden' => (int) ($trabajador->area?->orden ?? 0),
                'grupo_escala' => (string) ($cargo?->grupo_escala?->nombre ?? ''),
                'categoria' => substr($catCargo, 0, 1),
                'tiempo' => $tiempo,
                'descuentos' => $d,
                'regular' => $regular,
            ];
        }

        // Orden legacy: rh_areas.order + rh_gruposescala.nombgrupoescala DESC.
        // Dentro del mismo grupo de escala se mantiene el orden natural del
        // query (insert), igual que el legacy (sin criterio de desempate).
        usort($registros, function ($a, $b) {
            return ($a['area_orden'] <=> $b['area_orden'])
                ?: strcmp($a['nombarea'], $b['nombarea'])
                ?: strcmp($b['grupo_escala'], $a['grupo_escala']);
        });

        return ['registros' => $registros];
    }

    /**
     * SC-4-05 CONTROL DIARIO TIEMPO DE TRABAJO CHOFERES DE TRANSPORTACION
     * (reporte 11) — réplica de ModMovimientos::mostrar_control_choferes
     * (sistema de pago = 2).
     *
     * Por chofer: tiempo por día con 'T' (hojas de ruta vigentes del mes de
     * cierre, restando dias_trabajados), claves de incidencia encima (excepto
     * domingos 'D'), nocturnidad de los aforos del mes, descuentos por clave
     * y regular = min(ttotal + tiempo1, 240).
     */
    public function controlDiarioChoferes(int $mes, int $ano, ?int $entidadId = null): array
    {
        $entidadesPermitidas = $this->entidadesPermitidas($entidadId);
        $dias = Carbon::createFromDate($ano, $mes, 1)->daysInMonth;

        $choferes = Bolsa::query()
            ->where('activo', true)
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov')
                ->whereHas('plantilla.area', fn ($a) => $a->where('id_tipo_sistema_pago', 2)))
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->when(! empty($entidadesPermitidas),
                fn ($q) => $q->whereIn('id_entidad', $entidadesPermitidas),
                fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['cargo:id,nombre,id_categoria_cargo', 'cargo.categoria_cargo:id,nombre'])
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get();

        $registros = [];
        foreach ($choferes as $chofer) {
            $movimiento = $chofer->movimientosRrhh()
                ->whereNull('fbaja')->where('origen', 'mov')
                ->first();
            $catCargo = $chofer->cargoActual()?->categoria_cargo?->nombre ?? '';

            // Tiempos y nocturnidad de las transportaciones del mes (modelo1).
            $res = $this->choferCalc->calcularSalarioChofer($chofer->id, $mes, $ano);
            $ttotal = $res ? (float) $res['t_total'] : 0.0;
            $noct1 = $res ? (float) ($res['imp_nocturnidad_1'] >= 0 ? 0 : 0) : 0.0; // ver abajo: se recalcula
            $noct1 = 0.0; $noct2 = 0.0;
            if ($res) {
                // noct1/noct2 en HORAS: el detalle del motor los trae por aforo.
                foreach (($res['detalle'] ?? []) as $det) {
                    $noct1 += (float) $det['noct1'];
                    $noct2 += (float) $det['noct2'];
                }
            }

            // Tiempo por día: 'T' en el rango emisión→cierre de sus hojas de
            // ruta (no canceladas, cierre en el mes), quitando dias_trabajados.
            $tiempo = array_fill(0, $dias, '');
            for ($i = 0; $i < $dias; $i++) {
                if ((int) date('w', mktime(0, 0, 0, $mes, $i + 1, $ano)) === 0) {
                    $tiempo[$i] = 'D';
                }
            }
            $hrs = \App\Models\HojasRuta::query()
                ->where('cancelada', false)
                ->whereYear('fecha_cierre', $ano)
                ->whereMonth('fecha_cierre', $mes)
                ->where(fn ($q) => $q->where('id_chofer', $chofer->id)->orWhere('id_chofer2', $chofer->id))
                ->orderBy('fecha_emision')
                ->orderBy('fecha_cierre')
                ->get(['id', 'fecha_emision', 'fecha_cierre', 'id_chofer', 'id_chofer2', 'dias_trabajados']);
            foreach ($hrs as $hr) {
                $inicio = (int) ($hr->fecha_emision?->format('j') ?? 0);
                $final = (int) ($hr->fecha_cierre?->format('j') ?? 0);
                for ($a = $inicio; $a <= $final; $a++) {
                    if ($a >= 1 && $a <= $dias) {
                        $tiempo[$a - 1] = 'T';
                    }
                }
                // dias_trabajados quita 'T' (días sin trabajar dentro del rango).
                $dt = array_filter(array_map('intval', explode(';', (string) $hr->dias_trabajados)));
                foreach ($dt as $x) {
                    if ($x >= 1 && $x <= $dias) {
                        $tiempo[$x - 1] = '';
                    }
                }
            }
            $diasT = count(array_filter($tiempo, fn ($v) => $v === 'T'));

            // Descuentos por incidencia (la clave NO pisa los domingos).
            $d = array_fill_keys(['vac','cr','ai','lm','cm','lss','mov','int','fnt','ab','chm','rt','at','pm','sl','imp','o','tadrl','tiempo1','tiempo2'], 0.0);
            foreach ($this->incidenciasMes($chofer->id, $mes, $ano) as $inc) {
                for ($a = $inc['inicio']; $a <= $inc['final']; $a++) {
                    if ($a >= 1 && $a <= $dias && $tiempo[$a - 1] !== 'D') {
                        $tiempo[$a - 1] = substr($inc['clave'], 0, 2);
                    }
                }
                $col = self::MAPA_DESCUENTOS_SC405[$inc['clave']] ?? 'o';
                $d[$col] += $inc['tiempo'];
                $d['tiempo1'] += $inc['tiempo'];
            }
            $d['noct1'] = round($noct1, 2);
            $d['noct2'] = round($noct2, 2);

            $regular = round($ttotal + $d['tiempo1'], 2);
            if ($regular > 240) {
                $regular = 240.0;
            }
            if ($regular < 0) {
                $regular = 0.0;
            }

            $registros[] = [
                'id_bolsa' => $chofer->id,
                'nronomina' => $movimiento?->nronomina ?? $chofer->versat ?? '',
                'nombrecompleto' => $chofer->nombrecompleto,
                'categoria' => substr($catCargo, 0, 1),
                'tiempo' => $tiempo,
                'dias_trabajados' => $diasT,
                'descuentos' => $d,
                'regular' => $regular,
            ];
        }

        usort($registros, fn ($a, $b) => strcmp($a['nombrecompleto'], $b['nombrecompleto']));

        return ['registros' => $registros];
    }
}
