<?php

namespace App\Http\Controllers;

use App\Services\FusionCatalogosService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Formulario de fusión de catálogos (tipos de equipo, marcas y modelos).
 * Al fusionar, las fichas de vehículo (tipo_vehiculos) y demás tablas que
 * referencian el ítem se actualizan automáticamente.
 */
class FusionCatalogosController extends Controller
{
    public function __construct(private FusionCatalogosService $servicio)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('catalogo.editar');

        return Inertia::render('Catalogo/Fusionar', [
            'title' => 'Fusionar Catálogos',
            'tipos' => $this->servicio->tipos(),
            'opciones' => $this->servicio->opciones($request->input('tipo', 'tipos_equipos')),
            'tipoActual' => $request->input('tipo', 'tipos_equipos'),
        ]);
    }

    public function opciones(Request $request)
    {
        $this->authorize('catalogo.editar');

        $tipo = $request->input('tipo', 'tipos_equipos');

        return response()->json([
            'opciones' => $this->servicio->opciones($tipo),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('catalogo.editar');

        $validated = $request->validate([
            'tipo' => 'required|in:tipos_equipos,marcas,modelos,tipos_vehiculos',
            'origen_id' => 'required|integer',
            'destino_id' => 'required|integer|different:origen_id',
        ]);

        try {
            $resultado = $this->servicio->fusionar(
                $validated['tipo'],
                (int) $validated['origen_id'],
                (int) $validated['destino_id']
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['origen_id' => $e->getMessage()]);
        }

        return back()->with('success', "Fusión completada. Referencias re-apuntadas: {$resultado['referencias']}.");
    }
}
