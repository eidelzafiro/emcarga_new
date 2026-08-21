<?php

namespace App\Services\Reports;

use App\Models\Aforo;
use App\Models\Bolsa;
use App\Models\CombustibleDescarga;
use App\Models\Dieta;
use App\Models\Incidencia;
use App\Models\Penalizacion;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fase C — NÓMINA (33 reportes legacy):
 *  1.DATOS P/NOMINAS(A CALCULAR) (11), 2.DATOS P/NOMINAS(CHOFERES) (9), CERTIFICOS (13).
 * Versiones funcionales sobre tablas migradas de recursos humanos.
 */
class NominaReportService extends BaseReportService
{
    protected function mesFiltro(array $filtros): ?string
    {
        if (empty($filtros['mes'])) return null;
        try { return Carbon::parse($filtros['mes'])->format('Y-m'); } catch (\Exception) { return null; }
    }

    protected function rangoFiltros(array $filtros): array
    {
        $d = $filtros['desde'] ?? null;
        $h = $filtros['hasta'] ?? null;
        if (! $d && ! $h && ! empty($filtros['mes'])) {
            try { $m = Carbon::parse($filtros['mes']); $d = $m->copy()->startOfMonth()->toDateString(); $h = $m->copy()->endOfMonth()->toDateString(); } catch (\Exception) {}
        }
        return [$d, $h];
    }

    // ---------- 1.DATOS P/NOMINAS(A CALCULAR) ----------

    // 79 · INCIDENCIAS EN EL TIEMPO TRABAJADO
    public function incidenciasTiempoTrabajado(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Incidencia::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_inicio,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_inicio,'%Y-%m') as mes, SUM(importe) as importe, COUNT(*) as n")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_inicio,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Incidencias en el Tiempo Trabajado',
            [['key'=>'mes','label'=>'Mes'],['key'=>'importe','label'=>'Importe','num'=>true],['key'=>'n','label'=>'N','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 80 · PENALIZACIONES X PAGO ADICIONAL
    public function penalizacionesPagoAdicional(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Penalizacion::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha,'%Y-%m') as mes, SUM(importe) as importe, COUNT(*) as n")
            ->groupBy(DB::raw("DATE_FORMAT(fecha,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Penalizaciones por Pago Adicional',
            [['key'=>'mes','label'=>'Mes'],['key'=>'importe','label'=>'Importe','num'=>true],['key'=>'n','label'=>'N','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 87 · DATOS DE LOS TRABAJADORES
    public function datosTrabajadores(array $filtros): \Illuminate\Http\Response
    {
        $rows = Bolsa::query()->leftJoin('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->selectRaw("bolsa.ci as ci, CONCAT(COALESCE(bolsa.nombre,''),' ',COALESCE(bolsa.apellidos,'')) as trabajador, COALESCE(cargos.nombre,'') as cargo, bolsa.id_entidad as entidad")
            ->orderBy('bolsa.ci')->limit(800)->get();
        return $this->reporteTablaPdf('Datos de los Trabajadores',
            [['key'=>'ci','label'=>'CI'],['key'=>'trabajador','label'=>'Trabajador'],['key'=>'cargo','label'=>'Cargo'],['key'=>'entidad','label'=>'Entidad']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 88 / 1066 · RESUMEN PAGOS ADICIONALES / SALARIOS X CONCEPTOS E INCIDENCIAS
    public function resumenIncidencias(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Incidencia::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_inicio,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(id_tipo_incidencia,0) as tipo, SUM(importe) as importe, COUNT(*) as n")
            ->groupBy('id_tipo_incidencia')->orderBy('id_tipo_incidencia')->get();
        return $this->reporteTablaPdf('Resumen Salarios por Conceptos e Incidencias',
            [['key'=>'tipo','label'=>'Tipo Incidencia'],['key'=>'importe','label'=>'Importe','num'=>true],['key'=>'n','label'=>'N','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 90 · PAGOS X NOCTURNIDAD
    public function pagosNocturnidad(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Turno::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(inicio,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(inicio,'%Y-%m') as mes, SUM(noct1+noct2) as nocturnidad, SUM(tiempo) as tiempo")
            ->groupBy(DB::raw("DATE_FORMAT(inicio,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Pagos por Nocturnidad',
            [['key'=>'mes','label'=>'Mes'],['key'=>'nocturnidad','label'=>'Nocturnidad','num'=>true],['key'=>'tiempo','label'=>'Tiempo','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 91 · SALARIOS X SISTEMA DE PAGO (por trabajador)
    public function salariosSistemaPago(array $filtros): \Illuminate\Http\Response
    {
        $rows = Bolsa::query()
            ->leftJoin('incidencias', 'bolsa.id', '=', 'incidencias.id_bolsa')
            ->leftJoin('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->selectRaw("bolsa.ci as ci, CONCAT(COALESCE(bolsa.nombre,''),' ',COALESCE(bolsa.apellidos,'')) as trabajador, COALESCE(SUM(incidencias.importe),0) as incidencias")
            ->groupBy('bolsa.id','bolsa.ci','bolsa.nombre','bolsa.apellidos')
            ->orderBy('bolsa.ci')->limit(800)->get();
        return $this->reporteTablaPdf('Salarios por Sistema de Pago',
            [['key'=>'ci','label'=>'CI'],['key'=>'trabajador','label'=>'Trabajador'],['key'=>'incidencias','label'=>'Incidencias','num'=>true]],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 92 · CONTROL DIARIO DEL TRABAJO
    public function controlDiarioTrabajo(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Turno::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(inicio,'%Y-%m')"), $mes))
            ->selectRaw("inicio as fecha, final, tiempo, noct1, noct2, doblaje")
            ->orderBy('inicio')->limit(800)->get();
        return $this->reporteTablaPdf('Control Diario del Trabajo',
            [['key'=>'fecha','label'=>'Inicio'],['key'=>'final','label'=>'Final'],['key'=>'tiempo','label'=>'Tiempo','num'=>true],['key'=>'noct1','label'=>'Noct1','num'=>true],['key'=>'noct2','label'=>'Noct2','num'=>true],['key'=>'doblaje','label'=>'Doblaje','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1046 · EXPORTAR PRENOMINA A EXCEL
    public function exportarPrenomina(array $filtros): \Symfony\Component\HttpFoundation\Response
    {
        $rows = Bolsa::query()->leftJoin('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->leftJoin('incidencias', 'bolsa.id', '=', 'incidencias.id_bolsa')
            ->selectRaw("bolsa.ci as ci, CONCAT(COALESCE(bolsa.nombre,''),' ',COALESCE(bolsa.apellidos,'')) as trabajador, COALESCE(cargos.nombre,'') as cargo, COALESCE(SUM(incidencias.importe),0) as incidencias")
            ->groupBy('bolsa.id','bolsa.ci','bolsa.nombre','bolsa.apellidos','cargos.nombre')
            ->orderBy('bolsa.ci')->limit(800)->get();
        return $this->reporteTablaExcel('Prenómina',
            [['key'=>'ci','label'=>'CI'],['key'=>'trabajador','label'=>'Trabajador'],['key'=>'cargo','label'=>'Cargo'],['key'=>'incidencias','label'=>'Incidencias','num'=>true]],
            $rows->toArray(), null, ['landscape'=>true]);
    }

    // 4067 / 4068 · SALARIOS X SISTEMA DE PAGO CON RESULTADOS
    public function salariosResultados(array $filtros): \Illuminate\Http\Response
    {
        $rows = Bolsa::query()
            ->leftJoin('incidencias', 'bolsa.id', '=', 'incidencias.id_bolsa')
            ->selectRaw("bolsa.id_entidad as entidad, COALESCE(SUM(incidencias.importe),0) as importe, COUNT(DISTINCT bolsa.id) as trabajadores")
            ->groupBy('bolsa.id_entidad')->orderBy('bolsa.id_entidad')->get();
        return $this->reporteTablaPdf('Salarios por Sistema de Pago con Resultados',
            [['key'=>'entidad','label'=>'Entidad'],['key'=>'importe','label'=>'Importe','num'=>true],['key'=>'trabajadores','label'=>'Trabajadores','num'=>true]],
            $rows->toArray(), ['landscape'=>true]);
    }

    // ---------- 2.DATOS P/NOMINAS(CHOFERES) ----------

    // 119 · ANALISIS INDICADORES SALARIOS
    public function analisisIndicadoresSalarios(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(salario) as salario, SUM(viajes) as viajes")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Análisis Indicadores Salarios',
            [['key'=>'mes','label'=>'Mes'],['key'=>'salario','label'=>'Salario','num'=>true],['key'=>'viajes','label'=>'Viajes','num'=>true]],
            $rows->toArray(), ['periodo'=> "Mes", 'landscape'=>true]);
    }

    // 120 · ANALISIS DE LOS TIEMPOS
    public function analisisTiempos(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Turno::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(inicio,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(inicio,'%Y-%m') as mes, SUM(tiempo) as tiempo, SUM(noct1+noct2) as nocturnidad")
            ->groupBy(DB::raw("DATE_FORMAT(inicio,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Análisis de los Tiempos',
            [['key'=>'mes','label'=>'Mes'],['key'=>'tiempo','label'=>'Tiempo','num'=>true],['key'=>'nocturnidad','label'=>'Nocturnidad','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 121 · MODELO 1 CONTROL TRANSPORTACIONES
    public function modelo1Control(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("tractivos.codigo as tractivo, SUM(aforos.tn_real_total) as toneladas, SUM(aforos.km_total_total) as km_total, SUM(aforos.ingreso_mt) as ingreso_mt")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();
        return $this->reporteTablaPdf('Modelo 1 Control Transportaciones',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'km_total','label'=>'Km Total','num'=>true],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 122 · MODELO 1 CONTROL TRANSPORTACIONES (DETALLE)
    public function modelo1ControlDetalle(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("aforos.fecha_parte as fecha, tractivos.codigo as tractivo, aforos.tn_real_total as toneladas, aforos.km_total_total as km_total, aforos.ingreso_mt as ingreso_mt")
            ->orderBy('aforos.fecha_parte')->limit(800)->get();
        return $this->reporteTablaPdf('Modelo 1 Control Transportaciones (Detalle)',
            [['key'=>'fecha','label'=>'Fecha'],['key'=>'tractivo','label'=>'Tractivo'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'km_total','label'=>'Km Total','num'=>true],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 123 · LISTADO GARANTIA SALARIAL (choferes)
    public function listadoGarantiaSalarial(array $filtros): \Illuminate\Http\Response
    {
        $rows = Bolsa::query()->join('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->where('cargos.es_chofer', 1)
            ->selectRaw("bolsa.ci as ci, CONCAT(COALESCE(bolsa.nombre,''),' ',COALESCE(bolsa.apellidos,'')) as trabajador, COALESCE(cargos.nombre,'') as cargo")
            ->orderBy('bolsa.ci')->limit(800)->get();
        return $this->reporteTablaPdf('Listado Garantía Salarial',
            [['key'=>'ci','label'=>'CI'],['key'=>'trabajador','label'=>'Trabajador'],['key'=>'cargo','label'=>'Cargo']],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 124 · PAGO X GARANTIA SALARIAL
    public function pagoGarantiaSalarial(array $filtros): \Illuminate\Http\Response
    {
        $rows = Bolsa::query()->join('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->leftJoin('incidencias', 'bolsa.id', '=', 'incidencias.id_bolsa')
            ->where('cargos.es_chofer', 1)
            ->selectRaw("bolsa.ci as ci, CONCAT(COALESCE(bolsa.nombre,''),' ',COALESCE(bolsa.apellidos,'')) as trabajador, COALESCE(SUM(incidencias.importe),0) as incidencias")
            ->groupBy('bolsa.id','bolsa.ci','bolsa.nombre','bolsa.apellidos')
            ->orderBy('bolsa.ci')->limit(800)->get();
        return $this->reporteTablaPdf('Pago por Garantía Salarial',
            [['key'=>'ci','label'=>'CI'],['key'=>'trabajador','label'=>'Trabajador'],['key'=>'incidencias','label'=>'Incidencias','num'=>true]],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 125 · PAGO DE SALARIOS
    public function pagoSalarios(array $filtros): \Illuminate\Http\Response
    {
        $rows = Bolsa::query()
            ->leftJoin('incidencias', 'bolsa.id', '=', 'incidencias.id_bolsa')
            ->leftJoin('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->selectRaw("bolsa.ci as ci, CONCAT(COALESCE(bolsa.nombre,''),' ',COALESCE(bolsa.apellidos,'')) as trabajador, COALESCE(cargos.nombre,'') as cargo, COALESCE(SUM(incidencias.importe),0) as incidencias")
            ->groupBy('bolsa.id','bolsa.ci','bolsa.nombre','bolsa.apellidos','cargos.nombre')
            ->orderBy('bolsa.ci')->limit(800)->get();
        return $this->reporteTablaPdf('Pago de Salarios',
            [['key'=>'ci','label'=>'CI'],['key'=>'trabajador','label'=>'Trabajador'],['key'=>'cargo','label'=>'Cargo'],['key'=>'incidencias','label'=>'Incidencias','num'=>true]],
            $rows->toArray(), ['landscape'=>true]);
    }

    // 1047 · CONTROL DIARIO DEL TRABAJO DE LOS CHOFERES
    public function controlDiarioChoferes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Turno::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(inicio,'%Y-%m')"), $mes))
            ->selectRaw("inicio as fecha, final, tiempo, noct1, noct2")
            ->orderBy('inicio')->limit(800)->get();
        return $this->reporteTablaPdf('Control Diario del Trabajo de los Chóferes',
            [['key'=>'fecha','label'=>'Inicio'],['key'=>'final','label'=>'Final'],['key'=>'tiempo','label'=>'Tiempo','num'=>true],['key'=>'noct1','label'=>'Noct1','num'=>true],['key'=>'noct2','label'=>'Noct2','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1074 · EXPORTAR PRENOMINA A EXCEL (choferes)
    public function exportarPrenominaChoferes(array $filtros): \Symfony\Component\HttpFoundation\Response
    {
        $rows = Bolsa::query()->join('cargos', 'bolsa.id_cargo', '=', 'cargos.id')
            ->leftJoin('incidencias', 'bolsa.id', '=', 'incidencias.id_bolsa')
            ->where('cargos.es_chofer', 1)
            ->selectRaw("bolsa.ci as ci, CONCAT(COALESCE(bolsa.nombre,''),' ',COALESCE(bolsa.apellidos,'')) as trabajador, COALESCE(cargos.nombre,'') as cargo, COALESCE(SUM(incidencias.importe),0) as incidencias")
            ->groupBy('bolsa.id','bolsa.ci','bolsa.nombre','bolsa.apellidos','cargos.nombre')
            ->orderBy('bolsa.ci')->limit(800)->get();
        return $this->reporteTablaExcel('Prenómina Chóferes',
            [['key'=>'ci','label'=>'CI'],['key'=>'trabajador','label'=>'Trabajador'],['key'=>'cargo','label'=>'Cargo'],['key'=>'incidencias','label'=>'Incidencias','num'=>true]],
            $rows->toArray(), null, ['landscape'=>true]);
    }

    // ---------- CERTIFICOS ----------

    // 1048 / 1068 / 1072 · CERTIFICACION CHOFERES AREA COMERCIAL (y variantes)
    public function certificacionComercial(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(tn_real_total) as toneladas, SUM(km_total_total) as km_total, SUM(ingreso_mt) as ingreso_mt")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Certificación Chóferes Área Comercial',
            [['key'=>'mes','label'=>'Mes'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'km_total','label'=>'Km Total','num'=>true],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1055 · RESUMEN GASTOS DIETAS
    public function resumenGastosDietas(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Dieta::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha,'%Y-%m') as mes, SUM(monto) as monto")
            ->groupBy(DB::raw("DATE_FORMAT(fecha,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Resumen Gastos Dietas',
            [['key'=>'mes','label'=>'Mes'],['key'=>'monto','label'=>'Monto','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 1056 · RESUMEN INDICADORES EXPLOTACION X CHOFERES
    public function resumenIndicadoresExplotacion(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(viajes) as viajes, SUM(tn_real_total) as toneladas, SUM(salario) as salario")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Resumen Indicadores Explotación por Chóferes',
            [['key'=>'mes','label'=>'Mes'],['key'=>'viajes','label'=>'Viajes','num'=>true],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'salario','label'=>'Salario','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1057 · RESUMEN INGRESOS X CHOFERES
    public function resumenIngresosChoferes(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(ingreso_mt) as ingreso_mt, SUM(salario) as salario")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Resumen Ingresos por Chóferes',
            [['key'=>'mes','label'=>'Mes'],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true],['key'=>'salario','label'=>'Salario','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1059 · OPERACIONES REALIZADAS EN TALLER X OPERARIOS
    public function operacionesTallerOperarios(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = DB::table('ordenes_taller')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as mes, COUNT(*) as ordenes")
            ->groupBy(DB::raw("DATE_FORMAT(created_at,'%Y-%m')"))->orderBy('mes')->get();
        $data = $rows->map(fn ($r) => (array) $r)->toArray();
        return $this->reporteTablaPdf('Operaciones en Taller por Operarios',
            [['key'=>'mes','label'=>'Mes'],['key'=>'ordenes','label'=>'Órdenes','num'=>true]],
            $data, ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 1060 · CERTIFICACION CHOFERES GASTOS X EQUIPO
    public function certificacionGastosEquipo(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("tractivos.codigo as tractivo, SUM(aforos.ingreso_mt) as ingreso_mt")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();
        return $this->reporteTablaPdf('Certificación Chóferes Gastos por Equipo',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1062 · CERTIFICACION CHOFERES CUMPLIMIENTO DEL CDT X EQUIPO
    public function certificacionCumplimientoCdt(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->join('cartas_porte', 'aforos.id_carta_porte', '=', 'cartas_porte.id')
            ->join('hojas_ruta', 'cartas_porte.id_hoja_ruta', '=', 'hojas_ruta.id')
            ->join('tractivos', 'hojas_ruta.id_tractivo', '=', 'tractivos.id')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(aforos.fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("tractivos.codigo as tractivo, SUM(aforos.viajes) as viajes, SUM(aforos.tn_real_total) as toneladas")
            ->groupBy('tractivos.codigo')->orderBy('tractivos.codigo')->get();
        return $this->reporteTablaPdf('Certificación Chóferes Cumplimiento CDT',
            [['key'=>'tractivo','label'=>'Tractivo'],['key'=>'viajes','label'=>'Viajes','num'=>true],['key'=>'toneladas','label'=>'Toneladas','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1064 · CERTIFICACION CHOFERES INGRESO POR ALMACENAMIENTO
    public function certificacionAlmacenamiento(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(almacenaje_flete) as almacenaje")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Certificación Chóferes Ingreso por Almacenamiento',
            [['key'=>'mes','label'=>'Mes'],['key'=>'almacenaje','label'=>'Almacenaje','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 1073 · CERTIFICACION COMERCIAL TONELADAS KILOMETROS (EXCEL)
    public function certificacionComercialTonKm(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(tn_real_total) as toneladas, SUM(km_total_total) as km_total")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Certificación Chóferes Área Comercial Toneladas/Kilómetros',
            [['key'=>'mes','label'=>'Mes'],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'km_total','label'=>'Km Total','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }

    // 4025 · CERTIFICO INDICES CONSUMO
    public function certificoIndicesConsumo(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = CombustibleDescarga::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fdescarga,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fdescarga,'%Y-%m') as mes, SUM(saldo_lts) as lts, COUNT(*) as descargas")
            ->groupBy(DB::raw("DATE_FORMAT(fdescarga,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Certifico Índices Consumo',
            [['key'=>'mes','label'=>'Mes'],['key'=>'lts','label'=>'Litros','num'=>true],['key'=>'descargas','label'=>'Descargas','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos']);
    }

    // 4060 · REPORTE DE OPERACIONES EMCARGA
    public function reporteOperacionesEmcarga(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = Aforo::query()
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(fecha_parte,'%Y-%m') as mes, SUM(viajes) as viajes, SUM(tn_real_total) as toneladas, SUM(km_total_total) as km_total, SUM(ingreso_mt) as ingreso_mt, SUM(salario) as salario")
            ->groupBy(DB::raw("DATE_FORMAT(fecha_parte,'%Y-%m')"))->orderBy('mes')->get();
        return $this->reporteTablaPdf('Reporte de Operaciones EMCARGA',
            [['key'=>'mes','label'=>'Mes'],['key'=>'viajes','label'=>'Viajes','num'=>true],['key'=>'toneladas','label'=>'Toneladas','num'=>true],['key'=>'km_total','label'=>'Km Total','num'=>true],['key'=>'ingreso_mt','label'=>'Ingreso MT','num'=>true],['key'=>'salario','label'=>'Salario','num'=>true]],
            $rows->toArray(), ['periodo'=> $mes ? "Mes: $mes" : 'Todos', 'landscape'=>true]);
    }
}
