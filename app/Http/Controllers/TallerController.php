<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\ClasificacionOrdenTaller;
use App\Models\MotivosEntradaTaller;
use App\Models\OrdenesTaller;
use App\Services\OrdenTallerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TallerController extends Controller
{
    use EntidadScoping;

    public function __construct(private OrdenTallerService $ordenTallerService)
    {
    }

    public function index(Request $request)
    {
        $ordenes = OrdenesTaller::with(
            'tractivo:id,descripcion,placa',
            'tipoMantenimiento:id,nombre',
            'motivoEntrada:id,nombre',
            'clasificacion:id,nombre',
            'operaciones',
            'gastos',
            'movimientos',
        )
            ->when($request->search, fn ($q, $s) => $q->where('numero', 'like', "%{$s}%")
                ->orWhere('diagnostico', 'like', "%{$s}%"))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->orderByDesc('fecha_ingreso')
            ->paginate(20);

        return Inertia::render('Taller/Index', [
            'title' => 'Taller',
            'ordenes' => $ordenes,
            'filtros' => [
                'estados' => ['abierta', 'cerrada', 'cancelada'],
                'motivos_entrada' => MotivosEntradaTaller::orderBy('nombre')->get(['id', 'nombre']),
                'clasificaciones' => ClasificacionOrdenTaller::orderBy('nombre')->get(['id', 'nombre']),
                'tipos_operaciones' => \App\Models\TiposOperacione::orderBy('nombre')->get(['id', 'nombre', 'codigo']),
                'tipos_agregados' => \App\Models\TipoAgregado::orderBy('nombre')->get(['id', 'nombre']),
                'naves' => \App\Models\Nave::orderBy('nombre')->get(['id', 'nombre']),
                'vallas' => \App\Models\Valla::orderBy('nombre')->get(['id', 'nombre']),
                'tractivos' => \App\Models\Tractivo::orderBy('descripcion')->get(['id', 'descripcion', 'placa']),
                'operarios' => \App\Models\Bolsa::orderBy('nombre')->get(['id', 'nombre']),
            ],
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'nullable|string|max:50',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_mantenimiento' => 'nullable|exists:tipos_mantenimiento,id',
            'id_motivo_entrada' => 'nullable|exists:motivos_entrada_taller,id',
            'id_clasificacion' => 'nullable|exists:clasificaciones_ordenes_taller,id',
            'fecha_ingreso' => 'required|date',
            'hora_ingreso' => 'nullable|string|max:20',
            'fecha_salida' => 'nullable|date',
            'hora_salida' => 'nullable|string|max:20',
            'kilometraje' => 'nullable|numeric',
            'notas' => 'nullable|string',
            'ot_largo_plazo' => 'nullable|string|max:255',
            'combtaller' => 'nullable|numeric',
            'id_motor' => 'nullable|exists:motores,id',
            'id_taller' => 'nullable|exists:talleres,id',
        ]);

        $idEntidad = (int) session('entidad_activa_id') ?: null;

        try {
            $this->ordenTallerService->crear($validated, $idEntidad);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['id_tractivo' => $e->getMessage()]);
        }

        return redirect()->route('taller.index')
            ->with('success', 'Orden de taller creada correctamente.');
    }

    public function update(Request $request, OrdenesTaller $ordene)
    {
        $this->autorizarEntidad($ordene->id_entidad);

        $validated = $request->validate([
            'numero' => 'nullable|string|max:50',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_mantenimiento' => 'nullable|exists:tipos_mantenimiento,id',
            'id_motivo_entrada' => 'nullable|exists:motivos_entrada_taller,id',
            'id_clasificacion' => 'nullable|exists:clasificaciones_ordenes_taller,id',
            'fecha_ingreso' => 'required|date',
            'hora_ingreso' => 'nullable|string|max:20',
            'fecha_salida' => 'nullable|date',
            'hora_salida' => 'nullable|string|max:20',
            'kilometraje' => 'nullable|numeric',
            'notas' => 'nullable|string',
            'ot_largo_plazo' => 'nullable|string|max:255',
            'combtaller' => 'nullable|numeric',
            'id_motor' => 'nullable|exists:motores,id',
            'id_taller' => 'nullable|exists:talleres,id',
            'diagnostico' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        $ordene->update($validated);

        return redirect()->route('taller.index')
            ->with('success', 'Orden actualizada correctamente.');
    }

    /**
     * Cierra una OT.
     */
    public function cerrar(Request $request, OrdenesTaller $ordene)
    {
        $this->autorizarEntidad($ordene->id_entidad);

        $validated = $request->validate([
            'fecha_salida' => 'nullable|date',
            'hora_salida' => 'nullable|string|max:20',
        ]);

        $this->ordenTallerService->cerrar($ordene, $validated['fecha_salida'] ?? null, $validated['hora_salida'] ?? null);

        return back()->with('success', 'Orden cerrada correctamente.');
    }

    /**
     * Cancela una OT.
     */
    public function cancelar(Request $request, OrdenesTaller $ordene)
    {
        $this->autorizarEntidad($ordene->id_entidad);

        $this->ordenTallerService->cancelar($ordene);

        return back()->with('success', 'Orden cancelada correctamente.');
    }

    /**
     * Registra una operación en la OT.
     */
    public function agregarOperacion(Request $request, OrdenesTaller $ordene)
    {
        $this->autorizarEntidad($ordene->id_entidad);

        $validated = $request->validate([
            'id_tipo_operacion' => 'required|exists:tipos_operaciones,id',
            'id_operario' => 'nullable|exists:bolsa,id',
            'id_operario2' => 'nullable|exists:bolsa,id',
            'id_operario3' => 'nullable|exists:bolsa,id',
            'fecha_inicio' => 'nullable|date',
            'hora_inicio' => 'nullable|string|max:40',
            'fecha_final' => 'nullable|date',
            'hora_final' => 'nullable|string|max:40',
            'id_nave' => 'nullable|exists:naves,id',
            'id_valla' => 'nullable|exists:vallas,id',
        ]);

        $this->ordenTallerService->agregarOperacion($ordene, $validated);

        return back()->with('success', 'Operación agregada correctamente.');
    }

    /**
     * Registra una pieza/recurso de almacén en la OT.
     */
    public function agregarGasto(Request $request, OrdenesTaller $ordene)
    {
        $this->autorizarEntidad($ordene->id_entidad);

        $validated = $request->validate([
            'importe_me' => 'nullable|numeric',
            'vale' => 'nullable|string|max:10',
            'id_tipo_agregado' => 'nullable|exists:tipos_agregados,id',
            'nombre' => 'nullable|string|max:255',
            'cantidad' => 'nullable|numeric',
            'codigo_pieza' => 'nullable|string|max:10',
            'motivo' => 'nullable|string|max:255',
            'id_motor' => 'nullable|exists:motores,id',
        ]);

        $this->ordenTallerService->agregarGasto($ordene, $validated);

        return back()->with('success', 'Recurso de almacén agregado correctamente.');
    }

    /**
     * Registra un movimiento en taller (nave/valla).
     */
    public function agregarMovimiento(Request $request, OrdenesTaller $ordene)
    {
        $this->autorizarEntidad($ordene->id_entidad);

        $validated = $request->validate([
            'id_nave' => 'nullable|exists:naves,id',
            'id_valla' => 'nullable|exists:vallas,id',
            'fecha_inicio' => 'nullable|date',
            'hora_inicio' => 'nullable|string|max:10',
            'fecha_final' => 'nullable|date',
            'hora_final' => 'nullable|string|max:10',
            'observaciones' => 'nullable|string',
        ]);

        $this->ordenTallerService->agregarMovimiento($ordene, $validated);

        return back()->with('success', 'Movimiento en taller agregado correctamente.');
    }

    public function destroy(OrdenesTaller $ordene)
    {
        $this->autorizarEntidad($ordene->id_entidad);

        $ordene->delete();

        return redirect()->route('taller.index')
            ->with('success', 'Orden eliminada correctamente.');
    }
}
