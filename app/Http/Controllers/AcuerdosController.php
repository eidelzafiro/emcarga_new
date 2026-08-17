<?php

namespace App\Http\Controllers;

use App\Models\Acuerdo;
use App\Models\Cliente;
use App\Models\Lugare;
use App\Models\Producto;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcuerdosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $acuerdos = Acuerdo::with(['cliente:id,nombre', 'origen:id,nombre', 'destino:id,nombre', 'producto:id,nombre'])
            ->when($request->search, function ($q, $s) {
                $q->whereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('origen', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('destino', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('producto', fn ($c) => $c->where('nombre', 'like', "%{$s}%"));
            })
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->paginate(20);

        return Inertia::render('Acuerdos/Index', [
            'title' => 'Precios por acuerdo',
            'acuerdos' => $acuerdos,
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'lugares' => Lugare::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $validated['id_entidad'] = (int) session('entidad_activa_id');

        Acuerdo::create($validated);

        return redirect()->route('acuerdos.index')->with('success', 'Acuerdo creado correctamente.');
    }

    public function update(Request $request, Acuerdo $acuerdo)
    {
        $this->autorizarEntidad($acuerdo->id_entidad);

        $validated = $this->validar($request);
        $acuerdo->update($validated);

        return redirect()->route('acuerdos.index')->with('success', 'Acuerdo actualizado correctamente.');
    }

    public function destroy(Acuerdo $acuerdo)
    {
        $this->autorizarEntidad($acuerdo->id_entidad);

        $acuerdo->delete();

        return redirect()->route('acuerdos.index')->with('success', 'Acuerdo eliminado correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'id_cliente' => 'required|exists:clientes,id',
            'id_lugar_origen' => 'required|exists:lugares,id|different:id_lugar_destino',
            'id_lugar_destino' => 'required|exists:lugares,id',
            'id_producto' => 'required|exists:productos,id',
            'tarifa_ton' => 'required|numeric|min:0',
            'importe' => 'required|numeric|min:0',
            'activo' => 'sometimes|boolean',
        ]);
    }
}
