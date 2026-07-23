<?php
namespace App\Http\Controllers;
use App\Models\Lugare;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LugaresController extends Controller
{
    public function index(Request $request)
    {
        $lugares = Lugare::when($request->search, fn ($q, $s) => $q->where('nombre', 'like', "%{$s}%"))
            ->paginate(20);
        return Inertia::render('Lugares/Index', ['title' => 'Lugares', 'lugares' => $lugares, 'filters' => $request->only(['search'])]);
    }
}
