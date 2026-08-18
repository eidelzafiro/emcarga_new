<?php

namespace App\Http\Controllers;

use App\Models\SalarioAdministrativo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalariosAdministrativosController extends Controller
{
    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\SalarioAdministrativo::class);
        $items = SalarioAdministrativo::with(['movimiento', 'user'])
            ->orderBy('fecha', 'desc')
            ->paginate(20);

        return Inertia::render('SalariosAdministrativos/Index', [
            'title' => 'Salarios Administrativos',
            'items' => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\SalarioAdministrativo::class);
        $validated = $request->validate([
            'fecha' => 'required|date',
            'feriados' => 'required|numeric|min:0',
            'irregular' => 'required|numeric|min:0',
            'cpl' => 'required|numeric|min:0',
            'alimentos_extra' => 'required|numeric|min:0',
            'dias_taller' => 'required|numeric|min:0',
            'h_extra' => 'required|numeric|min:0',
            'imp_h_extra' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:borrador,aprobado,cerrado',
        ]);
        $validated['id_user'] = auth()->id();
        SalarioAdministrativo::create($validated);

        return redirect()->route('salarios-administrativos.index')->with('success', 'Salario creado correctamente.');
    }

    public function update(Request $request, SalarioAdministrativo $salariosAdministrativo)
    {
        
        $this->authorize('update', $salariosAdministrativo);
        $validated = $request->validate([
            'fecha' => 'required|date',
            'feriados' => 'required|numeric|min:0',
            'irregular' => 'required|numeric|min:0',
            'cpl' => 'required|numeric|min:0',
            'alimentos_extra' => 'required|numeric|min:0',
            'dias_taller' => 'required|numeric|min:0',
            'h_extra' => 'required|numeric|min:0',
            'imp_h_extra' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
            'estado' => 'required|in:borrador,aprobado,cerrado',
        ]);
        $salariosAdministrativo->update($validated);

        return redirect()->route('salarios-administrativos.index')->with('success', 'Salario actualizado correctamente.');
    }

    public function destroy(SalarioAdministrativo $salariosAdministrativo)
    {
        
        $this->authorize('delete', $salariosAdministrativo);
        $salariosAdministrativo->delete();

        return redirect()->route('salarios-administrativos.index')->with('success', 'Salario eliminado correctamente.');
    }
}
