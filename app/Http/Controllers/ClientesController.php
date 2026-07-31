<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientesController extends Controller
{
    public function index(Request $request)
    {
        $clientes = Cliente::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%")->orWhere('codigo', 'like', "%{$s}%"))
            ->when(true, function ($q) {
                $entidadId = (int) session('entidad_activa_id');
                if ($entidadId) {
                    $q->where('id_entidad', $entidadId);
                }
                return $q;
            })
            ->paginate(20);

        return Inertia::render('Clientes/Index', ['title' => 'Clientes', 'clientes' => $clientes, 'filters' => $request->only(['search'])]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:clientes,codigo|max:50',
            'nombre' => 'required|max:255',
            'razon_social' => 'nullable|max:255',
            'nit' => 'nullable|max:50',
            'direccion' => 'nullable|max:500',
            'telefono' => 'nullable|max:100',
            'email' => 'nullable|email|max:255',
            'contacto' => 'nullable|max:255',
        ]);
        $validated['id_entidad'] = (int) session('entidad_activa_id');
        Cliente::create($validated);

        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'codigo' => 'required|unique:clientes,codigo,'.$cliente->id.'|max:50',
            'nombre' => 'required|max:255',
            'razon_social' => 'nullable|max:255',
            'nit' => 'nullable|max:50',
            'direccion' => 'nullable|max:500',
            'telefono' => 'nullable|max:100',
            'email' => 'nullable|email|max:255',
            'contacto' => 'nullable|max:255',
        ]);
        $cliente->update($validated);

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }
}
