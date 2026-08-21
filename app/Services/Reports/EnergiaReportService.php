<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Fase D — ENERGÍA (5 reportes legacy, fuente Reportestec):
 *  360 CONSUMO ELÉCTRICO, 362 CONSUMO AGUA, 363 CONSUMO GAS MANUFACTURADO,
 *  364 CONTROL DE INCIDENCIAS, 368 CONTROL DE PORTADORES ENERGETICOS.
 *
 * NOTA: las tablas legacy `tec_electlecturas` / `tec_electdatos` están VACÍAS
 * en la BD `emcarga` (0 filas). Se implementan versiones funcionales que
 * consultan esas tablas; si no hay datos, el PDF indica "Sin datos migrados".
 */
class EnergiaReportService extends BaseReportService
{
    private function lecturasPorTipo(?string $mes, ?string $tipo): array
    {
        return DB::connection('legacy')->table('tec_electlecturas')
            ->join('tec_electdatos', 'tec_electlecturas.idelectdatos', '=', 'tec_electdatos.idelectdatos')
            ->when($tipo, fn ($q) => $q->where('tec_electdatos.tipo', $tipo))
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(tec_electlecturas.flectura,'%Y-%m')"), $mes))
            ->selectRaw("DATE_FORMAT(tec_electlecturas.flectura,'%Y-%m') as mes, SUM(tec_electlecturas.consumo) as consumo, COUNT(*) as lecturas")
            ->groupBy(DB::raw("DATE_FORMAT(tec_electlecturas.flectura,'%Y-%m')"))
            ->orderBy('mes')->get()->map(fn ($r) => (array) $r)->toArray();
    }

    private function consumoPdf(string $titulo, ?string $mes, ?string $tipo): \Illuminate\Http\Response
    {
        $rows = $this->lecturasPorTipo($mes, $tipo);
        $sinDatos = empty($rows) ? ' — SIN DATOS MIGRADOS' : '';

        return $this->reporteTablaPdf($titulo.$sinDatos,
            [
                ['key' => 'mes', 'label' => 'Mes'],
                ['key' => 'consumo', 'label' => 'Consumo', 'num' => true],
                ['key' => 'lecturas', 'label' => 'Lecturas', 'num' => true],
            ],
            $rows, ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 360
    public function consumoElectrico(array $filtros): \Illuminate\Http\Response
    {
        return $this->consumoPdf('Control del Consumo Eléctrico', $this->mesFiltro($filtros), 'electrico');
    }

    // 362
    public function consumoAgua(array $filtros): \Illuminate\Http\Response
    {
        return $this->consumoPdf('Control del Consumo de Agua', $this->mesFiltro($filtros), 'agua');
    }

    // 363
    public function consumoGas(array $filtros): \Illuminate\Http\Response
    {
        return $this->consumoPdf('Control del Consumo de Gas Manufacturado', $this->mesFiltro($filtros), 'gas');
    }

    // 364 · CONTROL DE INCIDENCIAS (por local)
    public function controlIncidencias(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = DB::connection('legacy')->table('tec_electlecturas')
            ->join('tec_electdatos', 'tec_electlecturas.idelectdatos', '=', 'tec_electdatos.idelectdatos')
            ->leftJoin('tec_electlocales', 'tec_electdatos.idunidad', '=', 'tec_electlocales.idelectlocales')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(tec_electlecturas.flectura,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tec_electlocales.electlocales,'') as local, SUM(tec_electlecturas.consumo) as consumo, COUNT(*) as lecturas")
            ->groupBy('tec_electlocales.electlocales')->orderBy('local')->get()->map(fn ($r) => (array) $r)->toArray();
        $sinDatos = empty($rows) ? ' — SIN DATOS MIGRADOS' : '';

        return $this->reporteTablaPdf('Control de Incidencias'.$sinDatos,
            [
                ['key' => 'local', 'label' => 'Local'],
                ['key' => 'consumo', 'label' => 'Consumo', 'num' => true],
                ['key' => 'lecturas', 'label' => 'Lecturas', 'num' => true],
            ],
            $rows, ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }

    // 368 · CONTROL DE PORTADORES ENERGETICOS (por tipo)
    public function portadoresEnergeticos(array $filtros): \Illuminate\Http\Response
    {
        $mes = $this->mesFiltro($filtros);
        $rows = DB::connection('legacy')->table('tec_electlecturas')
            ->join('tec_electdatos', 'tec_electlecturas.idelectdatos', '=', 'tec_electdatos.idelectdatos')
            ->when($mes, fn ($q) => $q->where(DB::raw("DATE_FORMAT(tec_electlecturas.flectura,'%Y-%m')"), $mes))
            ->selectRaw("COALESCE(tec_electdatos.tipo,'') as tipo, SUM(tec_electlecturas.consumo) as consumo, COUNT(*) as lecturas")
            ->groupBy('tec_electdatos.tipo')->orderBy('tipo')->get()->map(fn ($r) => (array) $r)->toArray();
        $sinDatos = empty($rows) ? ' — SIN DATOS MIGRADOS' : '';

        return $this->reporteTablaPdf('Control de Portadores Energéticos'.$sinDatos,
            [
                ['key' => 'tipo', 'label' => 'Tipo'],
                ['key' => 'consumo', 'label' => 'Consumo', 'num' => true],
                ['key' => 'lecturas', 'label' => 'Lecturas', 'num' => true],
            ],
            $rows, ['periodo' => $mes ? "Mes: $mes" : 'Todos', 'landscape' => true]);
    }
}
