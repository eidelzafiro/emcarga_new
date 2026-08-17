<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Neumatico;
use App\Models\NeumaticosMovimiento;
use App\Services\NeumaticoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NeumaticosController extends Controller
{
    use EntidadScoping;

    public function __construct(private NeumaticoService $neumaticoService)
    {
    }

    public function index(Request $request)
    {
        $neumaticos = Neumatico::with('tractivo:id,descripcion,placa', 'posicion:id,nombre')
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

        return Inertia::render('Neumaticos/Index', [
            'title' => 'Neumáticos',
            'neumaticos' => $neumaticos,
            'filtros' => [
                'estados' => ['activo', 'recauchado', 'regular', 'nuevo', 'baja'],
            ],
            'filters' => $request->only(['search', 'estado']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'folio' => 'required|string|max:50',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'medida' => 'nullable|string|max:50',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_instalacion' => 'nullable|date',
            'kilometraje' => 'nullable|numeric',
            'precio_mn' => 'nullable|numeric',
            'precio_me' => 'nullable|numeric',
            'id_posicion' => 'nullable|exists:posiciones_neumaticos,id',
            'fecha_fabricacion' => 'nullable|date',
            'balanceada' => 'nullable|boolean',
            'profinicial' => 'nullable|integer',
            'estado' => 'nullable|string|max:50',
        ]);

        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;
        $validated['folio'] = $request->input('folio', 'AUTOMATICO') === 'AUTOMATICO'
            ? $this->siguienteFolio()
            : $validated['folio'];

        $neumatico = Neumatico::create($validated);

        // Movimiento inicial de alta
        $this->neumaticoService->registrarMovimiento(
            $neumatico,
            (int) ($validated['id_tractivo'] ?? 0),
            $validated['fecha_instalacion'] ?? now()->toDateString(),
            $validated['kilometraje'] ?? 0,
            $validated['id_posicion'] ?? null,
            $validated['id_tractivo'] ? NeumaticoService::DESTINO_VEHICULO : null,
        );

        return redirect()->route('neumaticos.index')
            ->with('success', 'Neumático creado correctamente.');
    }

    public function update(Request $request, Neumatico $neumatico)
    {
        $this->autorizarEntidad($neumatico->id_entidad);

        $validated = $request->validate([
            'folio' => 'required|string|max:50',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'medida' => 'nullable|string|max:50',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_instalacion' => 'nullable|date',
            'kilometraje' => 'nullable|numeric',
            'precio_mn' => 'nullable|numeric',
            'precio_me' => 'nullable|numeric',
            'id_posicion' => 'nullable|exists:posiciones_neumaticos,id',
            'fecha_fabricacion' => 'nullable|date',
            'balanceada' => 'nullable|boolean',
            'profinicial' => 'nullable|integer',
            'estado' => 'nullable|string|max:50',
        ]);

        $neumatico->update($validated);

        return redirect()->route('neumaticos.index')
            ->with('success', 'Neumático actualizado correctamente.');
    }

    /**
     * Registra un movimiento de montaje/desmontaje (cambio de vehículo/posición).
     */
    public function registrarMovimiento(Request $request, Neumatico $neumatico)
    {
        $this->autorizarEntidad($neumatico->id_entidad);

        $validated = $request->validate([
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'fecha_montaje' => 'nullable|date',
            'km_instalado' => 'nullable|numeric',
            'id_posicion' => 'nullable|exists:posiciones_neumaticos,id',
            'id_destino' => 'nullable|integer',
            'observaciones' => 'nullable|string',
        ]);

        $this->neumaticoService->registrarMovimiento(
            $neumatico,
            (int) ($validated['id_tractivo'] ?? 0),
            $validated['fecha_montaje'] ?? null,
            isset($validated['km_instalado']) ? (float) $validated['km_instalado'] : null,
            $validated['id_posicion'] ?? null,
            $validated['id_destino'] ?? NeumaticoService::DESTINO_VEHICULO,
            $validated['observaciones'] ?? null,
        );

        return back()->with('success', 'Movimiento registrado correctamente.');
    }

    /**
     * Da de baja un neumático con motivo de rotura.
     */
    public function retirar(Request $request, Neumatico $neumatico)
    {
        $this->autorizarEntidad($neumatico->id_entidad);

        $validated = $request->validate([
            'fecha_retiro' => 'nullable|date',
            'km_retirado' => 'nullable|numeric',
            'id_tipo_rotura' => 'required|integer',
            'id_rotura' => 'required|integer',
        ]);

        $this->neumaticoService->retirar(
            $neumatico,
            $validated['fecha_retiro'] ?? null,
            isset($validated['km_retirado']) ? (float) $validated['km_retirado'] : null,
            $validated['id_tipo_rotura'],
            $validated['id_rotura'],
        );

        return back()->with('success', 'Neumático dado de baja correctamente.');
    }

    /**
     * Devuelve el historial de movimientos de un neumático.
     */
    public function movimientos(Neumatico $neumatico)
    {
        $this->autorizarEntidad($neumatico->id_entidad);

        return response()->json([
            'movimientos' => $neumatico->movimientos()
                ->with('tractivo:id,descripcion,placa')
                ->orderByDesc('id')->get(),
            'kms_recorridos' => $this->neumaticoService->kmsRecorridos($neumatico),
        ]);
    }

    public function destroy(Neumatico $neumatico)
    {
        $this->autorizarEntidad($neumatico->id_entidad);

        $neumatico->delete();

        return redirect()->route('neumaticos.index')
            ->with('success', 'Neumático eliminado correctamente.');
    }

    private function siguienteFolio(): string
    {
        $max = \App\Models\Neumatico::withTrashed()->max('id') ?? 0;

        return 'N' . str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
