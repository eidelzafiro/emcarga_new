<?php

namespace App\Http\Controllers;

use App\Models\Pizarra;
use Inertia\Inertia;

class PizarraController extends Controller
{
    public function index()
    {
        $registros = Pizarra::with(['tractivo:id,descripcion,placa', 'conductor:id,name'])
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'vehiculo' => $p->tractivo?->descripcion ?? '—',
                'placa' => $p->tractivo?->placa ?? '—',
                'conductor' => $p->conductor?->name ?? '—',
                'estado' => $p->estado,
                'ubicacion' => $p->ubicacion,
                'origen' => $p->origen,
                'destino' => $p->destino,
                'salida' => $p->salida?->format('H:i d/m/Y'),
                'llegada_estimada' => $p->llegada_estimada?->format('H:i d/m/Y'),
                'carga' => $p->carga,
                'tonelaje' => $p->tonelaje,
            ]);

        return Inertia::render('Pizarra/Index', [
            'title' => 'Pizarra de Vehículos',
            'registros' => $registros,
        ]);
    }

    public function datos()
    {
        $registros = Pizarra::with(['tractivo:id,descripcion,placa', 'conductor:id,name'])
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'vehiculo' => $p->tractivo?->descripcion ?? '—',
                'placa' => $p->tractivo?->placa ?? '—',
                'conductor' => $p->conductor?->name ?? '—',
                'estado' => $p->estado,
                'ubicacion' => $p->ubicacion,
                'origen' => $p->origen,
                'destino' => $p->destino,
                'salida' => $p->salida?->format('H:i d/m/Y'),
                'llegada_estimada' => $p->llegada_estimada?->format('H:i d/m/Y'),
                'carga' => $p->carga,
                'tonelaje' => $p->tonelaje,
            ]);

        return response()->json(['registros' => $registros]);
    }
}
