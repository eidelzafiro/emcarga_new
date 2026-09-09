<?php

namespace App\Services\Reports;

use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\Entidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

/**
 * Reportes del grupo OTROS / RRHH (migrado en R-1):
 * plazas vacantes, aseo tecnológico, cumpleaños del mes, personal con licencia,
 * documentos de choferes y modelo de aseo tecnológico.
 *
 * Filtros: 'unidad' (id de entidad) para listados; 'mes' (YYYY-MM) para cumpleaños.
 */
class OtrosReportService
{
    private function entidadIds(): array
    {
        $activa = (int) entidadActivaId();
        if (! $activa) {
            return [23];
        }

        return Entidad::idsPermitidos($activa);
    }

    private function entidadFiltro(array $filtros): array
    {
        if (! empty($filtros['unidad']) && is_numeric($filtros['unidad'])) {
            return [(int) $filtros['unidad']];
        }

        return $this->entidadIds();
    }

    private function rangoFiltros(array $filtros): array
    {
        $valor = $filtros['fecha'] ?? $filtros['mes'] ?? $filtros['mes1'] ?? null;
        if (! $valor) {
            $valor = now()->format('Y-m');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return [Carbon::parse($valor)->startOfDay(), Carbon::parse($valor)->endOfDay()];
        }

        return [Carbon::parse($valor.'-01')->startOfMonth(), Carbon::parse($valor.'-01')->endOfMonth()];
    }

    private function nombreMes(int $m): string
    {
        return match ($m) {
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
            default => '',
        };
    }

    private function reporteTablaPdf(string $titulo, array $columnas, array $filas, array $opts = []): \Illuminate\Http\Response
    {
        $periodo = $opts['periodo'] ?? '';
        $pdf = Pdf::loadHTML(view('reports.reporte_tabla', compact('titulo', 'columnas', 'filas', 'periodo'))->render());

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf']);
    }

    // 93 — LISTADO PLAZAS VACANTES
    public function plazasVacantes(array $filtros): \Illuminate\Http\Response
    {
        $ids = $this->entidadFiltro($filtros);

        $ocupadas = Bolsa::where('activo', 1)
            ->whereNotNull('id_cargo')
            ->pluck('id_cargo')
            ->all();

        $cargos = Cargo::with('entidad:id,nombre', 'calificador:id,nombre')
            ->whereIn('id_entidad', $ids)
            ->whereNotIn('id', $ocupadas)
            ->orderBy('nombre')
            ->get();

        $columnas = [
            ['key' => 'id', 'label' => 'No.', 'num' => false],
            ['key' => 'nombre', 'label' => 'Cargo', 'num' => false],
            ['key' => 'entidad', 'label' => 'Entidad', 'num' => false],
            ['key' => 'calificador', 'label' => 'Calificador', 'num' => false],
        ];

        $filas = $cargos->map(fn ($c) => [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'entidad' => optional($c->entidad)->nombre,
            'calificador' => optional($c->calificador)->nombre,
        ])->all();

        return $this->reporteTablaPdf('Listado de Plazas Vacantes', $columnas, $filas,
            ['periodo' => 'Unidad: '.(optional(Entidad::find($ids[0]))->nombre ?? '—')]);
    }

    // 130 — LISTADO ASEO TECNOLOGICO
    public function aseoTecnologico(array $filtros): \Illuminate\Http\Response
    {
        return $this->listadoControlPersonal(
            $filtros,
            'Listado de Aseo Tecnológico',
            'Unidad: '.(optional(Entidad::find($this->entidadFiltro($filtros)[0]))->nombre ?? '—')
        );
    }

    private function listadoControlPersonal(array $filtros, string $titulo, string $periodo): \Illuminate\Http\Response
    {
        $ids = $this->entidadFiltro($filtros);

        $personas = Bolsa::with('cargo:id,nombre', 'entidad:id,nombre')
            ->whereIn('id_entidad', $ids)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $columnas = [
            ['key' => 'nombre', 'label' => 'Nombre', 'num' => false],
            ['key' => 'ci', 'label' => 'CI', 'num' => false],
            ['key' => 'cargo', 'label' => 'Cargo', 'num' => false],
            ['key' => 'licencia', 'label' => 'Licencia vence', 'num' => false],
            ['key' => 'chequeo', 'label' => 'Chequeo vence', 'num' => false],
            ['key' => 'psicometrico', 'label' => 'Psicom. vence', 'num' => false],
            ['key' => 'reubicacion', 'label' => 'Reubic. vence', 'num' => false],
        ];

        $filas = $personas->map(fn ($p) => [
            'nombre' => $p->nombrecompleto,
            'ci' => $p->ci,
            'cargo' => optional($p->cargo)->nombre,
            'licencia' => optional($p->licencia_vencimiento)?->format('d/m/Y'),
            'chequeo' => optional($p->chequeo_medico_vencimiento)?->format('d/m/Y'),
            'psicometrico' => optional($p->psicometrico_vencimiento)?->format('d/m/Y'),
            'reubicacion' => optional($p->reubicacion_vencimiento)?->format('d/m/Y'),
        ])->all();

        return $this->reporteTablaPdf($titulo, $columnas, $filas, ['periodo' => $periodo]);
    }

    // 131 — LISTADO CUMPLEAÑOS DEL MES
    public function cumpleanosMes(array $filtros): \Illuminate\Http\Response
    {
        $ids = $this->entidadIds();
        $mes = ! empty($filtros['mes'])
            ? Carbon::parse($filtros['mes'].'-01')->month
            : now()->month;

        $personas = Bolsa::with('cargo:id,nombre', 'entidad:id,nombre')
            ->whereIn('id_entidad', $ids)
            ->where('activo', 1)
            ->whereRaw('MONTH(fecha_nacimiento) = ?', [$mes])
            ->orderByRaw('DAY(fecha_nacimiento)')
            ->get();

        $columnas = [
            ['key' => 'nombre', 'label' => 'Nombre', 'num' => false],
            ['key' => 'ci', 'label' => 'CI', 'num' => false],
            ['key' => 'fecha', 'label' => 'Nacimiento', 'num' => false],
            ['key' => 'cargo', 'label' => 'Cargo', 'num' => false],
            ['key' => 'entidad', 'label' => 'Entidad', 'num' => false],
        ];

        $filas = $personas->map(fn ($p) => [
            'nombre' => $p->nombrecompleto,
            'ci' => $p->ci,
            'fecha' => optional($p->fecha_nacimiento)?->format('d/m/Y'),
            'cargo' => optional($p->cargo)->nombre,
            'entidad' => optional($p->entidad)->nombre,
        ])->all();

        return $this->reporteTablaPdf('Cumpleaños del Mes', $columnas, $filas,
            ['periodo' => 'Mes: '.$this->nombreMes($mes)]);
    }

    // 1038 — LISTADO PERSONAL CON LICENCIA
    public function personalConLicencia(array $filtros): \Illuminate\Http\Response
    {
        $ids = $this->entidadFiltro($filtros);

        $personas = Bolsa::with('cargo:id,nombre', 'entidad:id,nombre')
            ->whereIn('id_entidad', $ids)
            ->where('activo', 1)
            ->where('tiene_licencia', 1)
            ->orderBy('nombre')
            ->get();

        $columnas = [
            ['key' => 'nombre', 'label' => 'Nombre', 'num' => false],
            ['key' => 'ci', 'label' => 'CI', 'num' => false],
            ['key' => 'cargo', 'label' => 'Cargo', 'num' => false],
            ['key' => 'categorias', 'label' => 'Categorías', 'num' => false],
            ['key' => 'licencia', 'label' => 'Licencia vence', 'num' => false],
        ];

        $filas = $personas->map(fn ($p) => [
            'nombre' => $p->nombrecompleto,
            'ci' => $p->ci,
            'cargo' => optional($p->cargo)->nombre,
            'categorias' => $p->categorias_licencia,
            'licencia' => optional($p->licencia_vencimiento)?->format('d/m/Y'),
        ])->all();

        return $this->reporteTablaPdf('Personal con Licencia', $columnas, $filas,
            ['periodo' => 'Unidad: '.(optional(Entidad::find($ids[0]))->nombre ?? '—')]);
    }

    // 1039 — LISTADO DOCUMENTOS CHOFERES
    public function documentosChoferes(array $filtros): \Illuminate\Http\Response
    {
        return $this->listadoControlPersonal(
            $filtros,
            'Documentos de Choferes',
            'Unidad: '.(optional(Entidad::find($this->entidadFiltro($filtros)[0]))->nombre ?? '—')
        );
    }

    // 1041 — MODELO ASEO TECNOLOGICO
    public function modeloAseoTecnologico(array $filtros): \Illuminate\Http\Response
    {
        return $this->listadoControlPersonal(
            $filtros,
            'Modelo de Aseo Tecnológico',
            'Unidad: '.(optional(Entidad::find($this->entidadFiltro($filtros)[0]))->nombre ?? '—')
        );
    }
}
