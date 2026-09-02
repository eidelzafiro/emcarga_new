<?php

namespace App\Http\Controllers;

use App\Services\DashboardTecnicoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardTecnicoController extends Controller
{
    public function __construct(protected DashboardTecnicoService $service) {}

    public function flota(Request $request)
    {
        $this->authorize('viewAny', \App\Models\OrdenesTaller::class);

        return Inertia::render('Tecnico/Flota', array_merge(
            ['title' => 'Dashboard · Flota Técnica'],
            $this->service->paraFlota($request)
        ));
    }

    public function taller(Request $request)
    {
        $this->authorize('viewAny', \App\Models\OrdenesTaller::class);

        return Inertia::render('Tecnico/Taller', array_merge(
            ['title' => 'Dashboard · Taller en Vivo'],
            $this->service->paraTaller($request)
        ));
    }

    public function componentes(Request $request)
    {
        $this->authorize('viewAny', \App\Models\OrdenesTaller::class);

        return Inertia::render('Tecnico/Componentes', array_merge(
            ['title' => 'Dashboard · Salud de Componentes'],
            $this->service->paraComponentes($request)
        ));
    }

    // ── Pizarra operativa (Tablero de flota + Operaciones + Taller) ──

    public function pizarraOperativa(Request $request)
    {
        $this->authorize('viewAny', \App\Models\OrdenesTaller::class);

        return Inertia::render('Tecnico/PizarraOperativa', array_merge(
            ['title' => 'Dashboard · Técnico'],
            $this->service->paraPizarraOperativa($request)
        ));
    }

    // ── Endpoints JSON (tiempo real / polling) ──

    public function datosFlota(Request $request)
    {
        return response()->json($this->service->paraFlota($request));
    }

    public function datosTaller(Request $request)
    {
        return response()->json($this->service->paraTaller($request));
    }

    public function datosComponentes(Request $request)
    {
        return response()->json($this->service->paraComponentes($request));
    }

    public function detalleFlota(Request $request)
    {
        return response()->json($this->service->detalleFlota($request));
    }

    public function datosPizarraOperativa(Request $request)
    {
        return response()->json($this->service->paraPizarraOperativa($request));
    }
}
