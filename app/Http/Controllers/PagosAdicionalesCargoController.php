<?php

namespace App\Http\Controllers;

use App\Models\PagosAdicionalesCargo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PagosAdicionalesCargoController extends Controller
{
    public function index()
    {
        $items = PagosAdicionalesCargo::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Pagos Adicionales de Cargo',
            'route' => 'pagos-adicionales-cargo',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Pago Adicional de Cargo',
            'route' => 'pagos-adicionales-cargo',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        PagosAdicionalesCargo::create($validated);

        return redirect()->route('pagos-adicionales-cargo.index')->with('success', 'Pago adicional de cargo creado.');
    }

    public function show($id)
    {
        $item = PagosAdicionalesCargo::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Pago Adicional de Cargo',
            'route' => 'pagos-adicionales-cargo',
        ]);
    }

    public function edit($id)
    {
        $item = PagosAdicionalesCargo::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Pago Adicional de Cargo',
            'route' => 'pagos-adicionales-cargo',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = PagosAdicionalesCargo::findOrFail($id);
        $item->update($validated);

        return redirect()->route('pagos-adicionales-cargo.index')->with('success', 'Pago adicional de cargo actualizado.');
    }

    public function destroy($id)
    {
        $item = PagosAdicionalesCargo::findOrFail($id);
        $item->delete();

        return redirect()->route('pagos-adicionales-cargo.index')->with('success', 'Pago adicional de cargo eliminado.');
    }
}
