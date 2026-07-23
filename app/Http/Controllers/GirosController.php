<?php

namespace App\Http\Controllers;

use App\Models\Giro;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GirosController extends Controller
{
    public function index(Request $request)
    {
        $giros = Giro::with('cliente:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('numero_carta_porte', 'like', "%{$s}%"))
            ->orderBy('fecha_parte', 'desc')
            ->paginate(20);

        return Inertia::render('Giros/Index', ['title' => 'Cartas Porte', 'giros' => $giros, 'filters' => $request->only(['search'])]);
    }
}
