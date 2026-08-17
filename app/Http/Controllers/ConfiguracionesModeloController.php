<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracioneModelo;
use App\Models\TipoModelo;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfiguracionesModeloController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $user = $request->user();
        $entidades = $this->entidadesPermitidas();

        $query = ConfiguracioneModelo::with('tipoModelo');

        if ($user->hasRole('SUPERADMIN')) {
            $query->where(function ($q) use ($entidades) {
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades)->orWhereNull('id_entidad');
                }
            });
        } else {
            if (! empty($entidades)) {
                $query->whereIn('id_entidad', $entidades);
            }
        }

        $query->when($request->search, function ($q, $search) {
            $q->where('nombre', 'like', "%{$search}%");
        });

        $query->when($request->codigo_tipo_modelo, function ($q, $tipo) {
            $q->where('codigo_tipo_modelo', $tipo);
        });

        $items = $query->orderBy('nombre')->paginate(20);

        $tiposQuery = TipoModelo::select('codigo', 'nombre')->orderBy('nombre');
        if (! empty($entidades)) {
            $tiposQuery->whereIn('id_entidad', $entidades);
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
        $this->autorizarEntidad($configuracionesModelo->id_entidad);

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
        $this->autorizarEntidad($configuracionesModelo->id_entidad);

        $configuracionesModelo->delete();

        return redirect()->route('configuraciones-modelo.index')->with('success', 'Configuración eliminada correctamente.');
    }
}
