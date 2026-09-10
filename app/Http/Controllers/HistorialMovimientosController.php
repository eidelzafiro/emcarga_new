<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\MovimientoRrhh;
use App\Models\Plantilla;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Historial de movimientos de empleados — réplica del legacy
 * system/application/controllers/Movimientos.php:
 * - Grid: relación trabajador ↔ plaza (plantilla = cargo + área), con
 *   tipomov (ALTAS/TRASLADOS/BAJAS/MOVIMIENTOS), fecha de alta (bolsa.falta)
 *   y fecha de baja (fbaja).
 * - Alta: crea el movimiento (tipomov=ALTAS), marca falta en la bolsa y
 *   ocupa la plaza de la plantilla (cubierta/cubierta2 según cubreplaza).
 * - Traslado: cambia la plantilla del movimiento, liberando la plaza
 *   anterior y ocupando la nueva.
 * - Baja: marca fbaja en el movimiento vigente, libera la plaza y limpia
 *   falta en la bolsa.
 */
class HistorialMovimientosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\HistorialMovimiento::class);

        $entidades = $this->entidadesPermitidas();

        $movimientos = MovimientoRrhh::query()
            ->with([
                'bolsa:id,nombre,apellidos,ci,versat,falta,id_entidad,id_cargo',
                'bolsa.cargo:id,nombre',
                'plantilla:id,id_cargo,id_area,cubierta,cubierta2,aprobada,propuesta,id_entidad',
                'plantilla.cargo:id,nombre',
                'plantilla.area:id,nombre',
            ])
            ->when($request->search, function ($q, $s) {
                $q->whereHas('bolsa', function ($b) use ($s) {
                    $b->where('nombre', 'like', "%{$s}%")
                        ->orWhere('apellidos', 'like', "%{$s}%")
                        ->orWhere('ci', 'like', "%{$s}%");
                });
            })
            ->when($request->tipo, fn ($q, $t) => $q->where('tipomov', $t))
            ->when($request->filled('solo_vigentes'), fn ($q) => $q->whereNull('fbaja'))
            ->when(! empty($entidades), fn ($q) => $q->whereHas('bolsa', fn ($b) => $b->whereIn('id_entidad', $entidades)))
            ->orderByDesc('fbaja')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (MovimientoRrhh $m) => $this->filaMovimiento($m));

        // Opciones para el diálogo de alta: SOLO trabajadores sin plaza
        // (sin movimiento vigente), como el legacy.
        $trabajadores = Bolsa::query()
            ->where('activo', true)
            ->whereDoesntHave('movimientosRrhh', fn ($q) => $q->whereNull('fbaja')->where('origen', 'mov'))
            ->when(! empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->orderBy('nombre')->orderBy('apellidos')
            ->get(['id', 'nombre', 'apellidos', 'ci', 'versat'])
            ->map(fn ($b) => [
                'id' => $b->id,
                'nombre' => $b->nombrecompleto,
                'ci' => $b->ci,
                'versat' => $b->versat,
            ]);

        $plazas = Plantilla::query()
            ->with(['cargo:id,nombre', 'area:id,nombre'])
            ->when(! empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->orderBy('id')
            ->get()
            ->map(function (Plantilla $p) {
                return [
                    'id' => $p->id,
                    'cargo' => $p->cargo?->nombre ?? '—',
                    'area' => $p->area?->nombre ?? '—',
                    'cubierta' => (int) $p->cubierta,
                    'cubierta2' => (int) $p->cubierta2,
                    'aprobada' => (int) $p->aprobada,
                    'label' => "#{$p->id} · {$p->cargo?->nombre} · {$p->area?->nombre}",
                ];
            });

        return Inertia::render('HistorialMovimientos/Index', [
            'title' => 'Historial de Movimientos',
            'historial' => $movimientos,
            'trabajadores' => $trabajadores,
            'plazas' => $plazas,
            'filters' => $request->only(['search', 'tipo', 'solo_vigentes']),
        ]);
    }

    /**
     * ALTA de un trabajador en una plaza (legacy Movimientos::actualizar_submit).
     */
    public function alta(Request $request)
    {
        $this->authorize('create', \App\Models\HistorialMovimiento::class);

        $validated = $request->validate([
            'id_bolsa' => ['required', 'exists:bolsa,id'],
            'nronomina' => ['required', 'integer'],
            'id_plantilla' => ['required', 'exists:plantilla,id'],
            'cubreplaza' => ['boolean'],
        ]);

        $bolsa = Bolsa::findOrFail($validated['id_bolsa']);

        // Un solo movimiento vigente por trabajador.
        if (MovimientoRrhh::where('id_bolsa', $bolsa->id)->whereNull('fbaja')->exists()) {
            return back()->withErrors(['id_bolsa' => 'El trabajador ya tiene un movimiento vigente (délo de baja o tráslelo).']);
        }

        DB::transaction(function () use ($validated, $bolsa) {
            MovimientoRrhh::create([
                'origen' => 'mov',
                'id_bolsa' => $validated['id_bolsa'],
                'nronomina' => $validated['nronomina'],
                'id_plantilla' => $validated['id_plantilla'],
                'cubreplaza' => $validated['cubreplaza'] ? 1 : 0,
                'tipomov' => 'ALTAS',
                'id_user' => auth()->id(),
            ]);

            // Ocupar plaza (cubierta si cubre plaza; cubierta2 si no).
            $plantilla = Plantilla::findOrFail($validated['id_plantilla']);
            if ($validated['cubreplaza']) {
                $plantilla->increment('cubierta');
            } else {
                $plantilla->increment('cubierta2');
            }

            // Fecha de alta en la bolsa (para cálculos de meses anteriores).
            $bolsa->update(['falta' => session('fecha_operaciones') ?: now()->toDateString()]);
        });

        return redirect()->route('historial-movimientos.index')->with('success', 'Alta registrada correctamente.');
    }

    /**
     * TRASLADO: cambia la plaza del movimiento vigente (legacy actualizar_movimientos).
     */
    public function traslado(Request $request)
    {
        $this->authorize('update', \App\Models\HistorialMovimiento::class);

        $validated = $request->validate([
            'id_movimiento' => ['required', 'exists:movimientos_rrhh,id'],
            'id_plantilla' => ['required', 'exists:plantilla,id'],
            'nronomina' => ['nullable', 'integer'],
            'cubreplaza' => ['boolean'],
        ]);

        $movimiento = MovimientoRrhh::whereNull('fbaja')->findOrFail($validated['id_movimiento']);
        $plantillaAnterior = $movimiento->plantilla;

        DB::transaction(function () use ($movimiento, $plantillaAnterior, $validated) {
            // Liberar plaza anterior.
            if ($plantillaAnterior) {
                if ((int) $movimiento->cubreplaza === 1) {
                    $plantillaAnterior->decrement('cubierta');
                } else {
                    $plantillaAnterior->decrement('cubierta2');
                }
            }

            // Ocupar la nueva.
            $nueva = Plantilla::findOrFail($validated['id_plantilla']);
            if (! empty($validated['cubreplaza'])) {
                $nueva->increment('cubierta');
            } else {
                $nueva->increment('cubierta2');
            }

            $movimiento->update([
                'id_plantilla' => $validated['id_plantilla'],
                'nronomina' => $validated['nronomina'] ?? $movimiento->nronomina,
                'cubreplaza' => ! empty($validated['cubreplaza']) ? 1 : 0,
                'tipomov' => 'TRASLADOS',
                'id_user' => auth()->id(),
            ]);
        });

        return redirect()->route('historial-movimientos.index')->with('success', 'Traslado registrado correctamente.');
    }

    /**
     * BAJA: cierra el movimiento vigente (legacy actualizar_baja).
     */
    public function baja(Request $request)
    {
        $this->authorize('update', \App\Models\HistorialMovimiento::class);

        $validated = $request->validate([
            'id_movimiento' => ['required', 'exists:movimientos_rrhh,id'],
        ]);

        $movimiento = MovimientoRrhh::whereNull('fbaja')->findOrFail($validated['id_movimiento']);

        DB::transaction(function () use ($movimiento) {
            $movimiento->update([
                'fbaja' => session('fecha_operaciones') ?: now()->toDateString(),
                'tipomov' => 'BAJAS',
                'id_user' => auth()->id(),
            ]);

            // Liberar plaza.
            if ($movimiento->plantilla) {
                if ((int) $movimiento->cubreplaza === 1) {
                    $movimiento->plantilla->decrement('cubierta');
                } else {
                    $movimiento->plantilla->decrement('cubierta2');
                }
            }

            // Limpiar falta de la bolsa + marcar inactivo.
            $movimiento->bolsa?->update([
                'falta' => null,
                'activo' => false,
            ]);
        });

        return redirect()->route('historial-movimientos.index')->with('success', 'Baja registrada correctamente.');
    }

    /**
     * Fila del grid (trabajador + área/cargo/plaza del movimiento).
     */
    private function filaMovimiento(MovimientoRrhh $m): array
    {
        $bolsa = $m->bolsa;

        return [
            'id' => $m->id,
            'id_bolsa' => $m->id_bolsa,
            'trabajador' => $bolsa ? $bolsa->nombrecompleto : '—',
            'ci' => $bolsa?->ci,
            'versat' => $bolsa?->versat,
            'nronomina' => $m->nronomina,
            'tipo' => $m->tipomov,
            'origen' => $m->origen,
            'vigente' => $m->fbaja === null,
            'fecha_alta' => $bolsa?->falta?->toDateString(),
            'fecha_baja' => $m->fbaja?->toDateString(),
            'cargo' => $m->plantilla?->cargo?->nombre,
            'area' => $m->plantilla?->area?->nombre,
            'plaza' => $m->id_plantilla,
            'cubreplaza' => (bool) $m->cubreplaza,
            'entidad_id' => $bolsa?->id_entidad,
        ];
    }
}
