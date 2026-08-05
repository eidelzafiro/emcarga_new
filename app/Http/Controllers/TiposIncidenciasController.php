<?php

namespace App\Http\Controllers;

use App\Models\TipoDeduccione;
use App\Models\TipoIncidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TiposIncidenciasController extends Controller
{
    public function index(Request $request)
    {
        $tipos = TipoIncidencia::with('tipoDeduccione')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%");
            }))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TiposIncidencias/Index', [
            'title' => 'Tipos de Incidencias',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
            'tipoDeducciones' => TipoDeduccione::select('id', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|max:255',
            'activo' => 'nullable|boolean',
            'tsuma' => 'nullable|boolean',
            'impsuma' => 'nullable|boolean',
            'id_tipo_deducciones' => 'nullable|exists:tipos_deducciones,id',
        ]);
        $validated['codigo'] = $this->generarCodigo();
        $validated['activo'] = $request->boolean('activo');
        $validated['tsuma'] = $request->boolean('tsuma');
        $validated['impsuma'] = $request->boolean('impsuma');
        TipoIncidencia::create($validated);

        return redirect()->route('tipos-incidencias.index')->with('success', 'Tipo de incidencia creado correctamente.');
    }

    public function update(Request $request, TipoIncidencia $tiposIncidencium)
    {
        $validated = $request->validate([
            'nombre' => 'required|max:255',
            'activo' => 'nullable|boolean',
            'tsuma' => 'nullable|boolean',
            'impsuma' => 'nullable|boolean',
            'id_tipo_deducciones' => 'nullable|exists:tipos_deducciones,id',
        ]);
        $validated['activo'] = $request->boolean('activo');
        $validated['tsuma'] = $request->boolean('tsuma');
        $validated['impsuma'] = $request->boolean('impsuma');
        $tiposIncidencium->update($validated);

        return redirect()->route('tipos-incidencias.index')->with('success', 'Tipo de incidencia actualizado correctamente.');
    }

    public function destroy(TipoIncidencia $tiposIncidencium)
    {
        $tiposIncidencium->delete();

        return redirect()->route('tipos-incidencias.index')->with('success', 'Tipo de incidencia eliminado correctamente.');
    }

    private function generarCodigo(): string
    {
        $max = DB::table('tipos_incidencias')
            ->selectRaw('MAX(CAST(codigo AS UNSIGNED)) as max_cod')
            ->value('max_cod');

        return str_pad((string) ((int) $max + 1), 2, '0', STR_PAD_LEFT);
    }
}