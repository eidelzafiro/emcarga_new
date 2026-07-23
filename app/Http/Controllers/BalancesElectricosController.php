<?php

namespace App\Http\Controllers;

use App\Models\BalanceElectrico;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BalancesElectricosController extends Controller
{
    public function index()
    {
        $items = BalanceElectrico::orderBy('id')->paginate(50);

        return Inertia::render('Catalogo/Index', [
            'items' => $items,
            'title' => 'Balances Eléctricos',
            'route' => 'balances-electricos',
        ]);
    }

    public function create()
    {
        return Inertia::render('Catalogo/Form', [
            'title' => 'Nuevo Balance Eléctrico',
            'route' => 'balances-electricos',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        BalanceElectrico::create($validated);

        return redirect()->route('balances-electricos.index')->with('success', 'Balance eléctrico creado.');
    }

    public function show($id)
    {
        $item = BalanceElectrico::findOrFail($id);

        return Inertia::render('Catalogo/Show', [
            'item' => $item,
            'title' => 'Balance Eléctrico',
            'route' => 'balances-electricos',
        ]);
    }

    public function edit($id)
    {
        $item = BalanceElectrico::findOrFail($id);

        return Inertia::render('Catalogo/Form', [
            'item' => $item,
            'title' => 'Editar Balance Eléctrico',
            'route' => 'balances-electricos',
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            // add validation rules
        ]);

        $item = BalanceElectrico::findOrFail($id);
        $item->update($validated);

        return redirect()->route('balances-electricos.index')->with('success', 'Balance eléctrico actualizado.');
    }

    public function destroy($id)
    {
        $item = BalanceElectrico::findOrFail($id);
        $item->delete();

        return redirect()->route('balances-electricos.index')->with('success', 'Balance eléctrico eliminado.');
    }
}
