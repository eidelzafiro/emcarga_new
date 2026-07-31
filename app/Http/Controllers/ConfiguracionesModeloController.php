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
        $user = $request->user();
        $entidadId = session('entidad_activa_id', $user->id_entidad);

        $query = ConfiguracioneModelo::with('tipoModelo');

        if ($user->hasRole('SUPERADMIN')) {
            $query->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId)
                ->orWhereNull('id_entidad'));
        } else {
            $query->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId));
        }

        $query->when($request->search, function ($q, $search) {
            $q->where('nombre', 'like', "%{$search}%");
        });

        $query->when($request->codigo_tipo_modelo, function ($q, $tipo) {
            $q->where('codigo_tipo_modelo', $tipo);
        });

        $items = $query->orderBy('nombre')->paginate(20);

        $tiposQuery = TipoModelo::select('codigo', 'nombre')->orderBy('nombre');
        if ($entidadId) {
            $tiposQuery->where('id_entidad', $entidadId);
        }
        $tiposModelo = $tiposQuery->get()
            ->map(fn ($t) => ['value' => $t->codigo, 'label' => $t->nombre]);

        return Inertia::render('ConfiguracionesModelo/Index', [
            'title' => 'Configuraciones de Modelo',
            'items' => $items,
            'tiposModelo' => $tiposModelo,
            'filters' => $request->only(['search', 'codigo_tipo_modelo']),
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
        $validated['id_entidad'] = session('entidad_activa_id', auth()->user()->id_entidad);
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
