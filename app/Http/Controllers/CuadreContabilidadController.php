<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\ReporteCosto;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CuadreContabilidadController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $this->authorize('viewAny', ReporteCosto::class);

        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $resumen = ReporteCosto::select(
            'id_tractivo',
            DB::raw('SUM(combustible_mn) as combustible_mn'),
            DB::raw('SUM(lubricante_mn) as lubricante_mn'),
            DB::raw('SUM(piezas_mn) as piezas_mn'),
            DB::raw('SUM(salario) as salario'),
            DB::raw('SUM(vacaciones) as vacaciones'),
            DB::raw('SUM(impuesto1) as impuesto1'),
            DB::raw('SUM(impuesto2) as impuesto2'),
            DB::raw('SUM(salario_total) as salario_total'),
            DB::raw('SUM(dietas) as dietas'),
            DB::raw('SUM(amortizacion_mn) as amortizacion_mn'),
            DB::raw('SUM(chapa) as chapa'),
            DB::raw('SUM(otros_gastos_mn) as otros_gastos_mn'),
            DB::raw('SUM(indirectos_admin_mn) as indirectos_admin_mn'),
            DB::raw('SUM(indirectos_taller_mn) as indirectos_taller_mn'),
            DB::raw('SUM(indirectos_mn) as indirectos_mn'),
            DB::raw('SUM(gastos_mn) as gastos_mn'),
            DB::raw('SUM(ingresos_mn) as ingresos_mn'),
            DB::raw('SUM(kms_total) as kms_total'),
            DB::raw('SUM(toneladas) as toneladas'),
            DB::raw('SUM(trafico) as trafico'),
            DB::raw('SUM(utilidad_mn) as utilidad_mn'),
            DB::raw('SUM(utilidad_mlc) as utilidad_mlc'),
            DB::raw('SUM(costo_mn) as costo_mn'),
            DB::raw('SUM(costo_mlc) as costo_mlc'),
            DB::raw('COUNT(*) as registros'),
        )
            ->whereYear('fecha_reporte', $anio)
            ->whereMonth('fecha_reporte', $mes)
            ->when(! empty($this->entidadesPermitidas()), function ($q) {
                $q->whereHas('tractivo', fn ($q2) => $q2->whereIn('id_entidad', $this->entidadesPermitidas()));
            })
            ->groupBy('id_tractivo')
            ->with('tractivo:id,codigo')
            ->orderBy('id_tractivo')
            ->get();

        $totales = [
            'combustible_mn' => $resumen->sum('combustible_mn'),
            'lubricante_mn' => $resumen->sum('lubricante_mn'),
            'piezas_mn' => $resumen->sum('piezas_mn'),
            'salario' => $resumen->sum('salario'),
            'vacaciones' => $resumen->sum('vacaciones'),
            'impuesto1' => $resumen->sum('impuesto1'),
            'impuesto2' => $resumen->sum('impuesto2'),
            'salario_total' => $resumen->sum('salario_total'),
            'dietas' => $resumen->sum('dietas'),
            'amortizacion_mn' => $resumen->sum('amortizacion_mn'),
            'chapa' => $resumen->sum('chapa'),
            'otros_gastos_mn' => $resumen->sum('otros_gastos_mn'),
            'indirectos_admin_mn' => $resumen->sum('indirectos_admin_mn'),
            'indirectos_taller_mn' => $resumen->sum('indirectos_taller_mn'),
            'indirectos_mn' => $resumen->sum('indirectos_mn'),
            'gastos_mn' => $resumen->sum('gastos_mn'),
            'ingresos_mn' => $resumen->sum('ingresos_mn'),
            'kms_total' => $resumen->sum('kms_total'),
            'toneladas' => $resumen->sum('toneladas'),
            'trafico' => $resumen->sum('trafico'),
            'utilidad_mn' => $resumen->sum('utilidad_mn'),
            'utilidad_mlc' => $resumen->sum('utilidad_mlc'),
            'costo_mn' => $resumen->sum('costo_mn'),
            'costo_mlc' => $resumen->sum('costo_mlc'),
        ];

        return Inertia::render('CuadreContabilidad/Index', [
            'title' => 'Cuadre Contabilidad',
            'resumen' => $resumen,
            'totales' => $totales,
            'fechaOperaciones' => $fechaOperaciones,
        ]);
    }
}
