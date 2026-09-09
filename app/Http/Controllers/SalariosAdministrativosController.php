<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Bolsa;
use App\Models\SalarioAdministrativo;
use App\Models\Turno;
use App\Services\SalarioAdminCalcService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalariosAdministrativosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request, SalarioAdminCalcService $service)
    {
        $this->authorize('viewAny', SalarioAdministrativo::class);

        $fechaOps = session('fecha_operaciones', now()->format('Y-m-d'));
        $mes = (int) (new \Carbon\Carbon($fechaOps))->format('m');
        $ano = (int) (new \Carbon\Carbon($fechaOps))->format('Y');

        $mes = (int) $request->input('mes', $mes);
        $ano = (int) $request->input('ano', $ano);
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $entidades = $this->entidadesPermitidas();
        $search = $request->input('search', '');
        $areaFilter = $request->input('area_filter', '');
        $horarioFilter = $request->input('horario_filter', '');
        $escalaFilter = $request->input('escala_filter', '');

        // Buscar ID de área "TRANSPORTE" para excluir
        $areaTransporteId = \App\Models\Area::whereRaw("UPPER(nombre) LIKE '%TRANSPOR%'")->value('id');

        $empleadoIds = Bolsa::where('activo', true)
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->when(!empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            // Excluir área de transporte
            ->when($areaTransporteId, function ($q) use ($areaTransporteId) {
                $q->where('id_area', '!=', $areaTransporteId);
            })
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")->orWhere('apellidos', 'like', "%{$search}%");
            }))
            ->when($areaFilter, fn ($q) => $q->where('id_area', $areaFilter))
            ->when($horarioFilter, fn ($q) => $q->whereHas('cargo', fn ($cq) => $cq->where('id_grupo_horario', $horarioFilter)))
            ->when($escalaFilter, fn ($q) => $q->whereHas('cargo', fn ($cq) => $cq->where('id_grupo_escala', $escalaFilter)))
            ->pluck('id');

        // Obtener opciones de filtros para los combos — filtradas por entidad
        $areasDisponibles = \App\Models\Area::whereIn('id', function ($q) use ($entidades, $areaTransporteId) {
            $q->select('id_area')->from('bolsa')
                ->where('activo', true)
                ->where('id_area', '!=', $areaTransporteId);
            if (!empty($entidades)) $q->whereIn('id_entidad', $entidades);
        })->orderBy('nombre')->get(['id', 'nombre']);
        $grupoHorarios = \App\Models\CatalogoItem::where('tipo', 'tipos_grupo_horario')->where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $grupoEscalas = \App\Models\CatalogoItem::where('tipo', 'tipos_grupo_escala')->where('activo', true)
            ->whereIn('id', function ($q) use ($entidades) {
                $q->select('id_grupo_escala')->from('cargos')
                    ->whereNotNull('id_grupo_escala');
                if (!empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }
            })
            ->orderBy('nombre')->get(['id', 'nombre']);

        // Ordenar IDs por área nombre → salario_final DESC (calculamos después)
        // Primero traemos los datos ordenados
        $empleadoIdsSorted = $empleadoIds->values();
        $totalEmpleados = $empleadoIdsSorted->count();
        $paginatedIds = $empleadoIdsSorted->slice(($page - 1) * $perPage)->values();

        $resultados = [];
        foreach ($paginatedIds as $idBolsa) {
            $empleado = Bolsa::with([
                'cargo:id,nombre,tarifa,cla,en_salario,id_categoria_cargo,id_fondo_tiempo,id_nivel_educacion,id_grupo_horario',
                'cargo.categoria_cargo:id,nombre',
                'cargo.fondo_tiempo:id,fondo_tiempo',
                'cargo.nivel_educacion:id,origen_id',
                'area:id,nombre',
            ])->find($idBolsa);
            if (!$empleado) continue;
            $calculado = $service->calcularSalarioEmpleado($empleado, $mes, $ano);
            if ($calculado) $resultados[] = $calculado;
        }

        // Ordenar: área ASC → salario_final DESC
        usort($resultados, function ($a, $b) {
            $cmp = strcmp($a['area'] ?? '', $b['area'] ?? '');
            if ($cmp !== 0) return $cmp;
            return ($b['salario_final'] ?? 0) <=> ($a['salario_final'] ?? 0);
        });

        $totalPages = (int) ceil($totalEmpleados / $perPage);

        return Inertia::render('SalariosAdministrativos/Index', [
            'title' => 'Salarios Administrativos',
            'salarios' => $resultados,
            'mes' => $mes,
            'ano' => $ano,
            'filters' => $request->only(['search', 'mes', 'area_filter', 'horario_filter', 'escala_filter']),
            'areasDisponibles' => $areasDisponibles,
            'grupoHorarios' => $grupoHorarios,
            'grupoEscalas' => $grupoEscalas,
            'paginacion' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalEmpleados,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SalarioAdministrativo::class);

        $validated = $request->validate([
            'id_movimiento' => 'required|exists:movimientos_rrhh,id',
            'fecha' => 'required|date',
            'irregular' => 'nullable|numeric|min:0',
            'feriados' => 'nullable|numeric|min:0',
            'dias_taller' => 'nullable|numeric|min:0|max:24',
            'h_extra' => 'nullable|numeric|min:0',
            'imp_h_extra' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $validated['id_user'] = auth()->id();

        SalarioAdministrativo::updateOrCreate(
            [
                'id_movimiento' => $validated['id_movimiento'],
                'fecha' => $validated['fecha'],
            ],
            $validated
        );

        return redirect()->back()->with('success', 'Datos de salario guardados correctamente.');
    }

    public function update(Request $request, SalarioAdministrativo $salario)
    {
        $this->authorize('update', $salario);

        $validated = $request->validate([
            'irregular' => 'nullable|numeric|min:0',
            'feriados' => 'nullable|numeric|min:0',
            'dias_taller' => 'nullable|numeric|min:0|max:24',
            'h_extra' => 'nullable|numeric|min:0',
            'imp_h_extra' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $salario->update($validated);

        return redirect()->back()->with('success', 'Datos de salario actualizados correctamente.');
    }

    public function destroy(SalarioAdministrativo $salario)
    {
        $this->authorize('delete', $salario);
        $salario->delete();

        return redirect()->back()->with('success', 'Registro de salario eliminado correctamente.');
    }

    public function guardarTurno(Request $request)
    {
        $validated = $request->validate([
            'id_movimiento' => 'required|exists:movimientos_rrhh,id',
            'turnos' => 'required|array|min:1',
            'turnos.*.inicio' => 'required|date',
            'turnos.*.tiempo' => 'nullable|numeric|min:0|max:24',
            'turnos.*.noct1' => 'nullable|numeric|min:0|max:24',
            'turnos.*.noct2' => 'nullable|numeric|min:0|max:24',
            'turnos.*.doblaje' => 'nullable|numeric|min:0|max:24',
        ]);

        $idMovimiento = $validated['id_movimiento'];

        foreach ($validated['turnos'] as $turno) {
            $inicio = $turno['inicio'];
            $tiempo = (float) ($turno['tiempo'] ?? 0);
            $noct1 = (float) ($turno['noct1'] ?? 0);
            $noct2 = (float) ($turno['noct2'] ?? 0);
            $doblaje = (float) ($turno['doblaje'] ?? 0);

            if ($tiempo <= 0 && $noct1 <= 0 && $noct2 <= 0 && $doblaje <= 0) {
                Turno::where('idmovimientos', $idMovimiento)
                    ->where('inicio', $inicio)
                    ->delete();
                continue;
            }

            Turno::updateOrCreate(
                [
                    'idmovimientos' => $idMovimiento,
                    'inicio' => $inicio,
                ],
                [
                    'final' => $inicio,
                    'tiempo' => $tiempo,
                    'noct1' => $noct1,
                    'noct2' => $noct2,
                    'doblaje' => $doblaje,
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'Turnos guardados correctamente.']);
    }
}
