<?php
namespace App\Http\Controllers;
use App\Models\Distancia;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DistanciasController extends Controller
{
    public function index(Request $request)
    {
        $distancias = Distancia::with('origen:id,nombre', 'destino:id,nombre')
            ->paginate(20);
        return Inertia::render('Distancias/Index', ['title' => 'Distancias', 'distancias' => $distancias, 'filters' => $request->only(['search'])]);
    }
}
