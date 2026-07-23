<?php

namespace App\Http\Controllers;

use App\Models\SolicitudesServicio;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SolicitudesController extends Controller
{
    public function index(Request $request)
    {
        $solicitudes = SolicitudesServicio::with('cliente:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('numero', 'like', "%{$s}%"))
            ->orderBy('fecha_solicitud', 'desc')
            ->paginate(20);

        return Inertia::render('Solicitudes/Index', ['title' => 'Solicitudes', 'solicitudes' => $solicitudes, 'filters' => $request->only(['search'])]);
    }
}
