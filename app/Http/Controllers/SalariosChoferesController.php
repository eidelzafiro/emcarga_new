<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Bolsa;
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

        $resultados = $service->calcularPorChofer($mes, $ano);

        $resultados = array_filter($resultados, function ($r) {
            $entidades = $this->entidadesPermitidas();
            if (empty($entidades)) return true;
            $chofer = Bolsa::find($r['id_bolsa']);
            return $chofer && in_array($chofer->id_entidad, $entidades);
        });

        $search = $request->input('search', '');
        if ($search) {
            $resultados = array_filter($resultados, function ($r) use ($search) {
                return str_contains(strtolower($r['nombre_completo']), strtolower($search));
            });
        }

        $resultados = array_values($resultados);

        $totalSalario = array_sum(array_column($resultados, 'salario_final'));
        $totalCla = array_sum(array_column($resultados, 'imp_cla'));
        $totalNoct1 = array_sum(array_column($resultados, 'imp_nocturnidad_1'));
        $totalNoct2 = array_sum(array_column($resultados, 'imp_nocturnidad_2'));
        $totalFeriados = array_sum(array_column($resultados, 'imp_feriados'));

        return Inertia::render('SalariosChoferes/Index', [
            'title' => 'Salario Choferes',
            'salarios' => $resultados,
            'mes' => $mes,
            'ano' => $ano,
            'filters' => $request->only(['search', 'mes', 'ano']),
            'resumen' => [
                'total_salario' => round($totalSalario, 2),
                'total_cla' => round($totalCla, 2),
                'total_nocturnidad' => round($totalNoct1 + $totalNoct2, 2),
                'total_feriados' => round($totalFeriados, 2),
                'total_choferes' => count($resultados),
            ],
        ]);
    }
}
