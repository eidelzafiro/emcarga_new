<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracioneModelo;
use App\Models\TipoModelo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfiguracionesModeloController extends Controller
{
    public function index(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');

        $items = ConfiguracioneModelo::with('tipoModelo')
            ->where('id_entidad', $entidadId)
            ->orderBy('nombre')
            ->paginate(20);

        $tiposModelo = TipoModelo::where('id_entidad', $entidadId)
            ->select('codigo', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($t) => ['value' => $t->codigo, 'label' => $t->nombre]);

        return Inertia::render('ConfiguracionesModelo/Index', [
            'items' => $items,
            'tiposModelo' => $tiposModelo,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|max:30',
            'codigo_tipo_modelo' => 'nullable|exists:tipos_modelo,codigo',
            'set_x' => 'nullable|integer',
            'set_y' => 'nullable|integer',
            'letra' => 'nullable|integer',
        ]);
        $validated['id_user'] = auth()->id();
        $validated['id_entidad'] = session('entidad_activa_id');
        ConfiguracioneModelo::create($validated);

        return redirect()->route('configuraciones-modelo.index')->with('success', 'Configuración creada correctamente.');
    }

    public function update(Request $request, ConfiguracioneModelo $configuracionesModelo)
    {
        $validated = $request->validate([
            'nombre' => 'required|max:30',
            'codigo_tipo_modelo' => 'nullable|exists:tipos_modelo,codigo',
            'set_x' => 'nullable|integer',
            'set_y' => 'nullable|integer',
            'letra' => 'nullable|integer',
        ]);
        $configuracionesModelo->update($validated);

        return redirect()->route('configuraciones-modelo.index')->with('success', 'Configuración actualizada correctamente.');
    }

    public function destroy(ConfiguracioneModelo $configuracionesModelo)
    {
        $configuracionesModelo->delete();

        return redirect()->route('configuraciones-modelo.index')->with('success', 'Configuración eliminada correctamente.');
    }
}
