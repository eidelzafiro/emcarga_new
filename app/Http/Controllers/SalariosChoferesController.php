<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Aforo;
use App\Models\Bolsa;
use App\Models\Tasa;
use App\Services\SalarioChoferCalcService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalariosChoferesController extends Controller
{
    use EntidadScoping;

    public function index(Request $request, SalarioChoferCalcService $service)
    {
        $this->authorize('viewAny', \App\Models\Salario::class);

        $fechaOps = session('fecha_operaciones', now()->format('Y-m-d'));
        $mes = (int) (new \Carbon\Carbon($fechaOps))->format('m');
        $ano = (int) (new \Carbon\Carbon($fechaOps))->format('Y');

        $mes = (int) $request->input('mes', $mes);
        $ano = (int) $request->input('ano', $ano);
        $page = (int) $request->input('page', 1);
        $perPage = 20;

        $entidadId = (int) entidadActivaId();
        $entidades = $this->entidadesPermitidas();
        $search = $request->input('search', '');
        $choferFilter = $request->input('chofer_filter', '');

        // Obtener choferes del mes (que tienen aforos vía hoja_ruta → carta_porte)
        $choferesDelMes = Bolsa::where('activo', true)
            ->where('tiene_licencia', true)
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja'))
            ->where(function ($q) use ($mes, $ano) {
                $q->whereHas('hojasRuta.cartasPorte.aforos', function ($aq) use ($mes, $ano) {
                    $aq->whereYear('fecha_parte', $ano)
                       ->whereMonth('fecha_parte', $mes);
                });
            })
            ->when(!empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get(['id', 'nombre', 'apellidos', 'ci']);

        $choferIds = $choferesDelMes->pluck('id');

        if ($choferFilter) {
            $choferIds = $choferIds->filter(fn ($id) => (string) $id === (string) $choferFilter);
        }

        if ($search) {
            $choferIds = $choferIds->filter(function ($id) use ($search, $choferesDelMes) {
                $chofer = $choferesDelMes->firstWhere('id', $id);
                if (!$chofer) return false;
                $full = strtolower($chofer->nombre . ' ' . $chofer->apellidos);
                return str_contains($full, strtolower($search));
            });
        }

        $choferIds = $choferIds->values();
        $totalChoferes = $choferIds->count();
        $paginatedIds = $choferIds->slice(($page - 1) * $perPage, $perPage)->values();

        $resultados = [];
        foreach ($paginatedIds as $idBolsa) {
            $calculado = $service->calcularSalarioChofer($idBolsa, $mes, $ano);
            if ($calculado) $resultados[] = $calculado;
        }

        // Ordenar alfabéticamente
        usort($resultados, fn ($a, $b) => strcmp($a['nombre_completo'], $b['nombre_completo']));

        $totalPages = (int) ceil($totalChoferes / $perPage);

        // Tasas filtradas por entidad activa
        $tasasQuery = Tasa::where('activo', true);
        if ($entidadId) {
            $tasasQuery->where('id_entidad', $entidadId);
        }
        $tasas = $tasasQuery->with('tipoCarga:id,nombre')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tasa', 'tasa2', 'id_tipo_carga', 'id_entidad']);

        return Inertia::render('SalariosChoferes/Index', [
            'title' => 'Salario Choferes',
            'salarios' => $resultados,
            'mes' => $mes,
            'ano' => $ano,
            'filters' => $request->only(['search', 'mes', 'ano', 'chofer_filter']),
            'tasas' => $tasas,
            'choferesDelMes' => $choferesDelMes->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombrecompleto,
                'ci' => $c->ci ?? '',
            ]),
            'paginacion' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalChoferes,
                'total_pages' => $totalPages,
            ],
        ]);
    }

    public function actualizarTasa(Request $request)
    {
        $validated = $request->validate([
            'id_aforo' => 'required|exists:aforos,id',
            'id_tasa' => 'required|exists:tasas,id',
        ]);

        $aforo = Aforo::findOrFail($validated['id_aforo']);
        $tasa = Tasa::findOrFail($validated['id_tasa']);

        $ingreso = (float) $aforo->ingreso_mt;
        $nuevoSalario = round($ingreso * (float) $tasa->tasa, 2);

        $aforo->update([
            'id_tasa' => $tasa->id,
            'tasa' => $tasa->tasa,
            'salario' => $nuevoSalario,
        ]);

        return response()->json([
            'success' => true,
            'tasa' => $tasa->tasa,
            'tasa_nombre' => $tasa->nombre,
            'salario' => $nuevoSalario,
        ]);
    }

    public function guardarTiempo(Request $request)
    {
        $validated = $request->validate([
            'id_aforo' => 'required|exists:aforos,id',
            'tiempo_total' => 'nullable|numeric|min:0',
            'km_total' => 'nullable|numeric|min:0',
            'tn_real' => 'nullable|numeric|min:0',
        ]);

        $aforo = Aforo::findOrFail($validated['id_aforo']);

        $campos = [];
        if (isset($validated['tiempo_total'])) $campos['tiempo_total'] = $validated['tiempo_total'];
        if (isset($validated['km_total'])) $campos['km_total_total'] = $validated['km_total'];
        if (isset($validated['tn_real'])) $campos['tn_real_total'] = $validated['tn_real'];

        $aforo->update($campos);

        return response()->json(['success' => true, 'message' => 'Datos guardados.']);
    }
}
