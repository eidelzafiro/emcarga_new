<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlertasController extends Controller
{
    public function index(Request $request)
    {
        $items = Alerta::with('user')
            ->orderBy('fecha_emision', 'desc')
            ->when($request->vencida, fn ($q, $v) => $q->where('vencida', $v === 'true'))
            ->paginate(20);

        return Inertia::render('Alertas/Index', [
            'items' => $items,
            'filters' => $request->only(['vencida']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mensaje' => 'required|string',
            'fecha_emision' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
            'id_perfil' => 'nullable|exists:perfiles_rh,id',
            'vencida' => 'boolean',
        ]);
        $validated['id_user'] = auth()->id();
        Alerta::create($validated);
        return redirect()->route('alertas.index')->with('success', 'Alerta creada correctamente.');
    }

    public function update(Request $request, Alerta $alerta)
    {
        $validated = $request->validate([
            'mensaje' => 'required|string',
            'fecha_emision' => 'nullable|date',
            'fecha_vencimiento' => 'nullable|date',
            'id_perfil' => 'nullable|exists:perfiles_rh,id',
            'vencida' => 'boolean',
        ]);
        $alerta->update($validated);
        return redirect()->route('alertas.index')->with('success', 'Alerta actualizada correctamente.');
    }

    public function destroy(Alerta $alerta)
    {
        $alerta->delete();
        return redirect()->route('alertas.index')->with('success', 'Alerta eliminada correctamente.');
    }
}
