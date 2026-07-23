<?php

namespace App\Http\Controllers;

use App\Models\ReporteCosto;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportesCostosController extends Controller
{
    public function index(Request $request)
    {
        $reportes = ReporteCosto::with(['tractivo', 'user'])
            ->when($request->search, fn ($q, $s) => $q->where('observaciones', 'like', "%{$s}%"))
            ->when($request->id_tractivo, fn ($q, $v) => $q->where('id_tractivo', $v))
            ->orderBy('fecha_reporte', 'desc')
            ->paginate(20);

        $tractivos = Tractivo::select('id', 'codigo', 'descripcion')->orderBy('codigo')->get();

        return Inertia::render('ReportesCostos/Index', [
            'reportes' => $reportes,
            'tractivos' => $tractivos,
            'filters' => $request->only(['search', 'id_tractivo']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha_reporte' => 'required|date',
            'id_tractivo' => 'required|exists:tractivos,id',
            'combustible_mn' => 'required|numeric|min:0',
            'lubricante_mn' => 'required|numeric|min:0',
            'piezas_mn' => 'required|numeric|min:0',
            'salario' => 'required|numeric|min:0',
            'vacaciones' => 'required|numeric|min:0',
            'impuesto1' => 'required|numeric|min:0',
            'impuesto2' => 'required|numeric|min:0',
            'salario_total' => 'required|numeric|min:0',
            'dietas' => 'required|numeric|min:0',
            'amortizacion_mn' => 'required|numeric|min:0',
            'chapa' => 'required|numeric|min:0',
            'otros_gastos_mn' => 'required|numeric|min:0',
            'indirectos_admin_mn' => 'required|numeric|min:0',
            'indirectos_taller_mn' => 'required|numeric|min:0',
            'indirectos_mn' => 'required|numeric|min:0',
            'gastos_mn' => 'required|numeric|min:0',
            'ingresos_mn' => 'required|numeric|min:0',
            'kms_total' => 'required|numeric|min:0',
            'toneladas' => 'required|numeric|min:0',
            'trafico' => 'required|numeric|min:0',
            'horas_taller' => 'required|integer|min:0',
            'utilidad_mn' => 'required|numeric',
            'utilidad_mlc' => 'required|numeric',
            'costo_mn' => 'required|numeric|min:0',
            'costo_mlc' => 'required|numeric|min:0',
            'costo_tn_kms' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);
        $validated['id_user'] = auth()->id();
        ReporteCosto::create($validated);
        return redirect()->route('reportes-costos.index')->with('success', 'Reporte creado correctamente.');
    }

    public function update(Request $request, ReporteCosto $reportesCosto)
    {
        $validated = $request->validate([
            'fecha_reporte' => 'required|date',
            'id_tractivo' => 'required|exists:tractivos,id',
            'combustible_mn' => 'required|numeric|min:0',
            'lubricante_mn' => 'required|numeric|min:0',
            'piezas_mn' => 'required|numeric|min:0',
            'salario' => 'required|numeric|min:0',
            'vacaciones' => 'required|numeric|min:0',
            'impuesto1' => 'required|numeric|min:0',
            'impuesto2' => 'required|numeric|min:0',
            'salario_total' => 'required|numeric|min:0',
            'dietas' => 'required|numeric|min:0',
            'amortizacion_mn' => 'required|numeric|min:0',
            'chapa' => 'required|numeric|min:0',
            'otros_gastos_mn' => 'required|numeric|min:0',
            'indirectos_admin_mn' => 'required|numeric|min:0',
            'indirectos_taller_mn' => 'required|numeric|min:0',
            'indirectos_mn' => 'required|numeric|min:0',
            'gastos_mn' => 'required|numeric|min:0',
            'ingresos_mn' => 'required|numeric|min:0',
            'kms_total' => 'required|numeric|min:0',
            'toneladas' => 'required|numeric|min:0',
            'trafico' => 'required|numeric|min:0',
            'horas_taller' => 'required|integer|min:0',
            'utilidad_mn' => 'required|numeric',
            'utilidad_mlc' => 'required|numeric',
            'costo_mn' => 'required|numeric|min:0',
            'costo_mlc' => 'required|numeric|min:0',
            'costo_tn_kms' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);
        $reportesCosto->update($validated);
        return redirect()->route('reportes-costos.index')->with('success', 'Reporte actualizado correctamente.');
    }

    public function destroy(ReporteCosto $reportesCosto)
    {
        $reportesCosto->delete();
        return redirect()->route('reportes-costos.index')->with('success', 'Reporte eliminado correctamente.');
    }
}
