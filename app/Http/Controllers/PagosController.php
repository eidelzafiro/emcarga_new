<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\TipoDocumento;
use App\Models\Moneda;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PagosController extends Controller
{
    public function index(Request $request)
    {
        $pagos = Pago::with(['tipoDocumento', 'moneda', 'user'])
            ->when($request->search, fn ($q, $s) => $q->where('concepto', 'like', "%{$s}%")->orWhere('numero_documento', 'like', "%{$s}%"))
            ->when($request->estado, fn ($q, $v) => $q->where('estado', $v))
            ->orderBy('fecha_pago', 'desc')
            ->paginate(20);

        $tiposDocumento = TipoDocumento::select('id', 'nombre')->orderBy('nombre')->get();
        $monedas = Moneda::select('id', 'codigo', 'nombre')->orderBy('codigo')->get();

        return Inertia::render('Pagos/Index', [
            'pagos' => $pagos,
            'tiposDocumento' => $tiposDocumento,
            'monedas' => $monedas,
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_tipo_documento' => 'nullable|exists:tipos_documentos,id',
            'id_moneda' => 'nullable|exists:monedas,id',
            'fecha_pago' => 'required|date',
            'numero_documento' => 'nullable|max:100',
            'monto' => 'required|numeric|min:0',
            'concepto' => 'nullable|max:255',
            'estado' => 'required|in:pendiente,aprobado,rechazado',
        ]);
        $validated['id_user'] = auth()->id();
        Pago::create($validated);
        return redirect()->route('pagos.index')->with('success', 'Pago creado correctamente.');
    }

    public function update(Request $request, Pago $pago)
    {
        $validated = $request->validate([
            'id_tipo_documento' => 'nullable|exists:tipos_documentos,id',
            'id_moneda' => 'nullable|exists:monedas,id',
            'fecha_pago' => 'required|date',
            'numero_documento' => 'nullable|max:100',
            'monto' => 'required|numeric|min:0',
            'concepto' => 'nullable|max:255',
            'estado' => 'required|in:pendiente,aprobado,rechazado',
        ]);
        $pago->update($validated);
        return redirect()->route('pagos.index')->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(Pago $pago)
    {
        $pago->delete();
        return redirect()->route('pagos.index')->with('success', 'Pago eliminado correctamente.');
    }
}
