<?php

namespace App\Http\Controllers;

use App\Models\Acuerdo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcuerdosController extends Controller
{
    public function index(Request $request)
    {
        $acuerdos = Acuerdo::with('cliente:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('descripcion', 'like', "%{$s}%"))
            ->paginate(20);

        return Inertia::render('Acuerdos/Index', ['title' => 'Acuerdos', 'acuerdos' => $acuerdos, 'filters' => $request->only(['search'])]);
    }
}
