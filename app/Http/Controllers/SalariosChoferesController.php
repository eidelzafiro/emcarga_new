<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Aforo;
use App\Models\Bolsa;
use App\Models\CartaPorte;
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

        // Obtener choferes del mes (que tienen aforos vía carta_porte)
        $choferesDelMes = Bolsa::where('activo', true)
            ->whereHas('documentos', fn ($q) => $q->where('tipo', 'LICENCIA'))
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->where(function ($q) use ($mes, $ano) {
                $q->whereHas('cartasPorte.aforos', function ($aq) use ($mes, $ano) {
                    $aq->whereYear('fecha_parte', $ano)
                       ->whereMonth('fecha_parte', $mes);
                })
                ->orWhereHas('cartasPorteChofer2.aforos', function ($aq) use ($mes, $ano) {
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

        // Choferes activos con licencia (para los combos del modal de edición).
        $choferes = Bolsa::where('activo', true)
            ->whereHas('documentos', fn ($q) => $q->where('tipo', 'LICENCIA'))
            ->whereHas('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->when(!empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->orderBy('nombre')
            ->orderBy('apellidos')
            ->get(['id', 'nombre', 'apellidos', 'ci']);

        return Inertia::render('SalariosChoferes/Index', [
            'title' => 'Salario Choferes',
            'salarios' => $resultados,
            'mes' => $mes,
            'ano' => $ano,
            'filters' => $request->only(['search', 'mes', 'ano', 'chofer_filter']),
            'tasas' => $tasas,
            'choferes' => $choferes->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombrecompleto,
                'ci' => $c->ci ?? '',
            ]),
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

        $nuevoSalario = $this->recalcularSalarioAforo($aforo, $tasa);

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

    /**
     * Edición integral de una carta de porte desde el módulo de salario:
     * chofer 1, chofer 2, tiempos y tasa. Al guardar se recalcula el salario.
     */
    public function editarDetalle(Request $request)
    {
        $validated = $request->validate([
            'id_aforo' => 'required|exists:aforos,id',
            'id_chofer' => 'required|exists:bolsa,id',
            'id_chofer2' => 'nullable|exists:bolsa,id',
            'id_tasa' => 'nullable|exists:tasas,id',
            'tiempo_otros' => 'nullable|numeric|min:0',
            'tiempo_movimiento' => 'nullable|numeric|min:0',
            'tiempo_carga' => 'nullable|numeric|min:0',
            'tiempo_descarga' => 'nullable|numeric|min:0',
            'tiempo_total' => 'nullable|numeric|min:0',
        ]);

        $aforo = Aforo::with('cartaPorte')->findOrFail($validated['id_aforo']);
        $cp = $aforo->cartaPorte;
        if (!$cp) {
            return response()->json(['success' => false, 'message' => 'La carta de porte no existe.'], 422);
        }

        // Choferes de la carta de porte.
        $cp->update([
            'id_chofer' => $validated['id_chofer'],
            'id_chofer2' => $validated['id_chofer2'] ?: null,
        ]);

        // Tiempos y tasas del aforo. KMS/TN/Ingreso son de SOLO LECTURA en la
        // UI (datos del aforo original) y no se editan desde aquí.
        $campos = [];
        foreach (['tiempo_otros', 'tiempo_movimiento', 'tiempo_carga', 'tiempo_descarga', 'tiempo_total'] as $campo) {
            if (array_key_exists($campo, $validated)) {
                $campos[$campo] = $validated[$campo];
            }
        }

        $tasa = null;
        if (!empty($validated['id_tasa'])) {
            $tasa = Tasa::findOrFail($validated['id_tasa']);
            $campos['id_tasa'] = $tasa->id;
            $campos['tasa'] = $tasa->tasa;
        }

        // Recalcular salario por tasa una vez actualizados choferes y tasa.
        $campos['salario'] = $this->recalcularSalarioAforo($aforo, $tasa, $cp);

        $aforo->update($campos);

        return response()->json(['success' => true, 'message' => 'Carta de porte actualizada y salario recalculado.']);
    }

    /**
     * Salario por TRT de un aforo (réplica de SalarioChoferCalcService), usando
     * la tasa dada y la condición de doble chofer de la carta de porte.
     */
    private function recalcularSalarioAforo(Aforo $aforo, ?Tasa $tasa, ?CartaPorte $cp = null): float
    {
        $cp = $cp ?? $aforo->cartaPorte;

        $ingreso = (float) $aforo->ingreso_mt;
        $almFlete = (float) ($aforo->almacenaje_flete ?? 0);
        $salalm = 0;

        $entidad = \App\Models\Entidad::find((int) entidadActivaId());
        $almacenaje = $entidad ? (float) $entidad->almacenaje : 0.0;

        $esDobleChofer = $cp && $cp->id_chofer2 && $cp->id_chofer2 != ($cp->id_chofer ?? 0);

        if ($esDobleChofer) {
            if ($almFlete > 0) {
                $ingreso = round($ingreso - $almFlete, 2);
                $salalm = round(($almFlete / 2) * $almacenaje, 2);
            } else {
                $ingreso = round($ingreso / 2, 2);
            }
        } elseif ($almFlete > 0) {
            $ingreso = round($ingreso - $almFlete, 2);
            $salalm = round($almFlete * $almacenaje, 2);
        }

        $coef = (float) ($aforo->tasa ?? 0);
        if ($tasa) {
            $coef = $esDobleChofer && (float) $tasa->tasa2 > 0
                ? (float) $tasa->tasa2
                : (float) $tasa->tasa;
        }

        return round($ingreso * $coef + $salalm, 2);
    }
}
