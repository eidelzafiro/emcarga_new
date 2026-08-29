<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\ClasificacionOrdenTaller;
use App\Models\GastosOrden;
use App\Models\MotivosEntradaTaller;
use App\Models\MovimientosTaller;
use App\Models\OrdenesOperacione;
use App\Models\OrdenesTaller;
use App\Services\OrdenTallerService;
use Carbon\Carbon;
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
        
        $this->authorize('viewAny', \App\Models\OrdenesTaller::class);

        // Mes/año de operaciones: ancla las cerradas a su fecha de cierre.
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $ordenes = OrdenesTaller::with(
            'tractivo:id,codigo,placa',
            'tipoMantenimiento:id,nombre',
            'motivoEntrada:id,nombre',
            'clasificacion:id,nombre',
            'operaciones',
            'gastos',
            'movimientos',
        )
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q2) use ($s) {
                    $q2->where('numero', 'like', "%{$s}%")
                        ->orWhere('diagnostico', 'like', "%{$s}%");
                });
            })
            ->when($request->id_tractivo, fn ($q, $v) => $q->where('id_tractivo', $v))
            ->when($request->id_motivo_entrada, fn ($q, $v) => $q->where('id_motivo_entrada', $v))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->when(true, function ($q) use ($anio, $mes, $request) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                // Por defecto: solo abiertas (todas) y cerradas en el mes de
                // operaciones. Si el usuario filtra por estado explícito, se respeta.
                if (! $request->estado) {
                    $q->where(function ($q2) use ($anio, $mes) {
                        $q2->where('estado', 'abierta')
                            ->orWhere(function ($q3) use ($anio, $mes) {
                                $q3->where('estado', 'cerrada')
                                    ->whereYear('fecha_salida', $anio)
                                    ->whereMonth('fecha_salida', $mes);
                            });
                    });
                }

                return $q;
            })
            ->orderByDesc('fecha_ingreso')
            ->paginate($request->integer('per_page', 50));

        return Inertia::render('Taller/Index', [
            'title' => 'Taller',
            'ordenes' => $ordenes,
            'filtros' => [
                'estados' => ['abierta', 'cerrada', 'cancelada'],
                'motivos_entrada' => \App\Support\Catalogos::opciones('motivos_entrada_taller'),
                'clasificaciones' => \App\Support\Catalogos::opciones('clasificaciones_ordenes_taller'),
                'tipos_operaciones' => \App\Models\TiposOperacione::orderBy('nombre')->get(['id', 'nombre', 'codigo']),
                'tipos_agregados' => \App\Models\TipoAgregado::orderBy('nombre')->get(['id', 'nombre']),
                'naves' => \App\Models\Nave::orderBy('nombre')->get(['id', 'nombre']),
                'vallas' => \App\Models\Valla::orderBy('nombre')->get(['id', 'nombre']),
                'tractivos' => \App\Models\Tractivo::orderBy('codigo')->get(['id', 'codigo', 'placa']),
                'operarios' => \App\Models\Bolsa::orderBy('nombre')->get(['id', 'nombre']),
            ],
            'filters' => $request->only(['search', 'estado', 'id_tractivo', 'id_motivo_entrada']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\OrdenesTaller::class);
        $validated = $request->validate([
            'numero' => 'nullable|string|max:50',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_mantenimiento' => 'nullable|exists:tipos_mantenimiento,id',
            'id_motivo_entrada' => 'nullable|exists:catalogo_items,id',
            'id_clasificacion' => 'nullable|exists:catalogo_items,id',
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

        $idEntidad = (int) entidadActivaId() ?: null;

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
        
        $this->authorize('update', $ordene);
        $this->autorizarEntidad($ordene->id_entidad);

        $validated = $request->validate([
            'numero' => 'nullable|string|max:50',
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_mantenimiento' => 'nullable|exists:tipos_mantenimiento,id',
            'id_motivo_entrada' => 'nullable|exists:catalogo_items,id',
            'id_clasificacion' => 'nullable|exists:catalogo_items,id',
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

    /**
     * Actualiza una operación de la OT.
     */
    public function actualizarOperacion(Request $request, OrdenesTaller $ordene, OrdenesOperacione $operacione)
    {
        $this->autorizarEntidad($ordene->id_entidad);
        abort_unless($operacione->id_orden_taller === $ordene->id, 404);

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

        $this->ordenTallerService->actualizarOperacion($operacione, $validated);

        return back()->with('success', 'Operación actualizada correctamente.');
    }

    /**
     * Elimina una operación de la OT.
     */
    public function eliminarOperacion(Request $request, OrdenesTaller $ordene, OrdenesOperacione $operacione)
    {
        $this->autorizarEntidad($ordene->id_entidad);
        abort_unless($operacione->id_orden_taller === $ordene->id, 404);

        $this->ordenTallerService->eliminarOperacion($operacione);

        return back()->with('success', 'Operación eliminada correctamente.');
    }

    /**
     * Actualiza una pieza/recurso de almacén de la OT.
     */
    public function actualizarGasto(Request $request, OrdenesTaller $ordene, GastosOrden $gasto)
    {
        $this->autorizarEntidad($ordene->id_entidad);
        abort_unless($gasto->id_orden_taller === $ordene->id, 404);

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

        $this->ordenTallerService->actualizarGasto($gasto, $validated);

        return back()->with('success', 'Pieza actualizada correctamente.');
    }

    /**
     * Elimina una pieza/recurso de almacén de la OT.
     */
    public function eliminarGasto(Request $request, OrdenesTaller $ordene, GastosOrden $gasto)
    {
        $this->autorizarEntidad($ordene->id_entidad);
        abort_unless($gasto->id_orden_taller === $ordene->id, 404);

        $this->ordenTallerService->eliminarGasto($gasto);

        return back()->with('success', 'Pieza eliminada correctamente.');
    }

    /**
     * Actualiza un movimiento en taller de la OT.
     */
    public function actualizarMovimiento(Request $request, OrdenesTaller $ordene, MovimientosTaller $movimiento)
    {
        $this->autorizarEntidad($ordene->id_entidad);
        abort_unless($movimiento->id_orden_taller === $ordene->id, 404);

        $validated = $request->validate([
            'id_nave' => 'nullable|exists:naves,id',
            'id_valla' => 'nullable|exists:vallas,id',
            'fecha_inicio' => 'nullable|date',
            'hora_inicio' => 'nullable|string|max:10',
            'fecha_final' => 'nullable|date',
            'hora_final' => 'nullable|string|max:10',
            'observaciones' => 'nullable|string',
        ]);

        $this->ordenTallerService->actualizarMovimiento($movimiento, $validated);

        return back()->with('success', 'Movimiento actualizado correctamente.');
    }

    /**
     * Elimina un movimiento en taller de la OT.
     */
    public function eliminarMovimiento(Request $request, OrdenesTaller $ordene, MovimientosTaller $movimiento)
    {
        $this->autorizarEntidad($ordene->id_entidad);
        abort_unless($movimiento->id_orden_taller === $ordene->id, 404);

        $this->ordenTallerService->eliminarMovimiento($movimiento);

        return back()->with('success', 'Movimiento eliminado correctamente.');
    }

    public function destroy(OrdenesTaller $ordene)
    {
        
        $this->authorize('delete', $ordene);
        $this->autorizarEntidad($ordene->id_entidad);

        $ordene->delete();

        return redirect()->route('taller.index')
            ->with('success', 'Orden eliminada correctamente.');
    }
}
