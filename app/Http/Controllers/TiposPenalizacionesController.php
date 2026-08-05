<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\TipoPagoAdicionale;
use App\Models\TipoPenalizacione;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TiposPenalizacionesController extends Controller
{
    public function index(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');

        $tipos = TipoPenalizacione::with(['area', 'tipoPagoAdicional'])
            ->where(function ($q) use ($entidadId) {
                if ($entidadId) {
                    $q->whereHas('area', fn ($sq) => $sq->where('id_entidad', $entidadId))
                        ->orWhereNull('area_id');
                }
            })
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%");
            }))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('TipoPenalizaciones/Index', [
            'title' => 'Tipos de Penalizaciones',
            'tipos' => $tipos,
            'filters' => $request->only(['search']),
            'areas' => Area::select('id', 'nombre')->orderBy('nombre')->get(),
            'sistemasPago' => TipoPagoAdicionale::select('id', 'nombre')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|max:255',
            'area_id' => 'nullable|exists:areas,id',
            'tipo_pago_adicional_id' => 'nullable|exists:tipos_pagos_adicionales,id',
            'porcentaje' => 'nullable|numeric|min:0|max:100',
        ]);
        $validated['id_entidad'] = (int) session('entidad_activa_id');
        $validated['codigo'] = $this->generarCodigo();
        TipoPenalizacione::create($validated);

        return redirect()->route('tipos-penalizaciones.index')->with('success', 'Tipo de penalización creado correctamente.');
    }

    public function update(Request $request, TipoPenalizacione $tiposPenalizacione)
    {
        $validated = $request->validate([
            'nombre' => 'required|max:255',
            'area_id' => 'nullable|exists:areas,id',
            'tipo_pago_adicional_id' => 'nullable|exists:tipos_pagos_adicionales,id',
            'porcentaje' => 'nullable|numeric|min:0|max:100',
        ]);
        $tiposPenalizacione->update($validated);

        return redirect()->route('tipos-penalizaciones.index')->with('success', 'Tipo de penalización actualizado correctamente.');
    }

    public function destroy(TipoPenalizacione $tiposPenalizacione)
    {
        $tiposPenalizacione->delete();

        return redirect()->route('tipos-penalizaciones.index')->with('success', 'Tipo de penalización eliminado correctamente.');
    }

    private function generarCodigo(): string
    {
        $max = DB::table('tipos_penalizaciones')
            ->selectRaw('MAX(CAST(codigo AS UNSIGNED)) as max_cod')
            ->value('max_cod');

        return str_pad((string) ((int) $max + 1), 2, '0', STR_PAD_LEFT);
    }
}
