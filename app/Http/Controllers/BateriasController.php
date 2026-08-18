<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Bateria;
use App\Services\BateriaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BateriasController extends Controller
{
    use EntidadScoping;

    public function __construct(private BateriaService $bateriaService)
    {
    }

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Bateria::class);
        $baterias = Bateria::with('tractivo:id,descripcion,placa', 'motivoBaja:id,nombre')
            ->when($request->search, fn ($q, $s) => $q->where('folio', 'like', "%{$s}%")
                ->orWhere('marca', 'like', "%{$s}%"))
            ->when($request->estado, fn ($q, $e) => $q->where('estado', $e))
            ->when(true, function ($q) {
                $entidades = $this->entidadesPermitidas();
                if (! empty($entidades)) {
                    $q->whereIn('id_entidad', $entidades);
                }

                return $q;
            })
            ->orderByDesc('id')
            ->paginate(20);

        return Inertia::render('Baterias/Index', [
            'title' => 'Baterías',
            'baterias' => $baterias,
            'filtros' => [
                'estados' => ['activa', 'baja'],
                'motivos_baja' => \App\Models\MotivosBajaBaterium::orderBy('nombre')->get(['id', 'nombre']),
                'destinos' => \App\Models\DestinoAgregado::orderBy('nombre')->get(['id', 'nombre']),
            ],
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\Bateria::class);
        $validated = $request->validate([
            'folio' => 'required|string|max:50',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_instalacion' => 'nullable|date',
            'voltaje' => 'nullable|integer',
            'amperaje' => 'nullable|integer',
            'precio_mn' => 'nullable|numeric',
            'precio_me' => 'nullable|numeric',
            'estado' => 'nullable|string|max:50',
        ]);

        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;
        $validated['folio'] = $request->input('folio', 'AUTOMATICO') === 'AUTOMATICO'
            ? $this->siguienteFolio()
            : $validated['folio'];

        $bateria = Bateria::create($validated);

        // Movimiento inicial de alta
        $this->bateriaService->registrarMovimiento(
            $bateria,
            (int) ($validated['id_tractivo'] ?? 0),
            $validated['fecha_instalacion'] ?? now()->toDateString(),
            $validated['id_tractivo'] ? BateriaService::DESTINO_VEHICULO : null,
        );

        return redirect()->route('baterias.index')
            ->with('success', 'Batería creada correctamente.');
    }

    public function update(Request $request, Bateria $bateria)
    {
        
        $this->authorize('update', $bateria);
        $this->autorizarEntidad($bateria->id_entidad);

        $validated = $request->validate([
            'folio' => 'required|string|max:50',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_instalacion' => 'nullable|date',
            'voltaje' => 'nullable|integer',
            'amperaje' => 'nullable|integer',
            'precio_mn' => 'nullable|numeric',
            'precio_me' => 'nullable|numeric',
            'estado' => 'nullable|string|max:50',
        ]);

        $bateria->update($validated);

        return redirect()->route('baterias.index')
            ->with('success', 'Batería actualizada correctamente.');
    }

    /**
     * Registra un movimiento de batería (montar/desmontar/cambiar).
     */
    public function registrarMovimiento(Request $request, Bateria $bateria)
    {
        $this->autorizarEntidad($bateria->id_entidad);

        $validated = $request->validate([
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_movimiento' => 'nullable|date',
            'id_destino' => 'nullable|integer',
            'observaciones' => 'nullable|string',
        ]);

        $this->bateriaService->registrarMovimiento(
            $bateria,
            (int) ($validated['id_tractivo'] ?? 0),
            $validated['fecha_movimiento'] ?? null,
            $validated['id_destino'] ?? null,
            $validated['observaciones'] ?? null,
        );

        return back()->with('success', 'Movimiento registrado correctamente.');
    }

    /**
     * Da de baja una batería con motivo obligatorio.
     */
    public function darDeBaja(Request $request, Bateria $bateria)
    {
        $this->autorizarEntidad($bateria->id_entidad);

        $validated = $request->validate([
            'fecha_baja' => 'nullable|date',
            'id_motivo_baja' => 'required|exists:motivos_baja_bateria,id',
            'id_destino' => 'nullable|integer',
        ]);

        $this->bateriaService->darDeBaja(
            $bateria,
            $validated['fecha_baja'] ?? null,
            (int) $validated['id_motivo_baja'],
            $validated['id_destino'] ?? null,
        );

        return back()->with('success', 'Batería dada de baja correctamente.');
    }

    public function destroy(Bateria $bateria)
    {
        
        $this->authorize('delete', $bateria);
        $this->autorizarEntidad($bateria->id_entidad);

        $bateria->delete();

        return redirect()->route('baterias.index')
            ->with('success', 'Batería eliminada correctamente.');
    }

    private function siguienteFolio(): string
    {
        $max = \App\Models\Bateria::withTrashed()->max('id') ?? 0;

        return 'B' . str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
