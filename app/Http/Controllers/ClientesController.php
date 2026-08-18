<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Moneda;
use App\Models\Organismo;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientesController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Cliente::class);
        $clientes = Cliente::with('organismo:id,nombre,abreviatura', 'moneda:id,codigo,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")
                ->orWhere('codigo', 'like', "%{$s}%")
                ->orWhere('nrocontrato', 'like', "%{$s}%"))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderBy('nombre')
            ->paginate(20);

        return Inertia::render('Clientes/Index', [
            'title' => 'Clientes',
            'clientes' => $clientes,
            'organismos' => Organismo::where('activo', true)->orderBy('abreviatura')->get(['id', 'nombre', 'abreviatura']),
            'monedas' => Moneda::where('activo', true)->orderBy('codigo')->get(['id', 'codigo', 'nombre', 'simbolo']),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Cliente::class);
        $validated = $this->validar($request);
        $validated['id_entidad'] = (int) session('entidad_activa_id');

        Cliente::create($validated);

        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }

    public function update(Request $request, Cliente $cliente)
    {
        
        $this->authorize('update', $cliente);
        $this->autorizarEntidad($cliente->id_entidad);

        $validated = $this->validar($request, $cliente);
        $cliente->update($validated);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        
        $this->authorize('delete', $cliente);
        $this->autorizarEntidad($cliente->id_entidad);

        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        $unique = $cliente ? 'unique:clientes,codigo,'.$cliente->id : 'unique:clientes,codigo';

        return $request->validate([
            'codigo' => 'required|'.$unique.'|max:50',
            'nombre' => 'required|max:255',
            'nrocontrato' => 'nullable|max:50',
            'falta' => 'required|date',
            'fvencimiento' => 'required|date',
            'codreup' => 'nullable|max:120',
            'idorganismos' => 'nullable|exists:organismos,id',
            'idmonedas' => 'nullable|exists:monedas,id',
            'nit' => 'nullable|max:50',
            'direccion' => 'nullable|max:500',
            'email' => 'nullable|email|max:255',
            'emailfacturacion' => 'nullable|email|max:200',
            'notas' => 'nullable|max:600',
            'agenciamn' => 'nullable|max:100',
            'ctamn' => 'nullable|max:50',
            'telefono' => 'nullable|max:100',
            'contacto' => 'nullable|max:255',
            'razon_social' => 'nullable|max:255',
            'activo' => 'sometimes|boolean',
        ]);
    }
}
