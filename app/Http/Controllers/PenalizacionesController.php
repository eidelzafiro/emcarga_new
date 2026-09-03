<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\CatalogoItem;
use App\Models\Penalizacion;
use App\Http\Controllers\Traits\EntidadScoping;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PenalizacionesController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Penalizacion::class);
        $entidades = $this->entidadesPermitidas();

        $fechaOps = session('fecha_operaciones');
        $fechaOperaciones = $fechaOps ? Carbon::parse($fechaOps) : Carbon::now();

        $query = Penalizacion::with(['bolsa', 'tipoPenalizacion', 'areaPenalizada', 'pagoAdicional'])
            ->when(!empty($entidades), fn ($q) => $q->whereHas('bolsa', fn ($sq) => $sq->whereIn('id_entidad', $entidades)))
            ->when($fechaOperaciones, function ($q) use ($fechaOperaciones) {
                $q->whereMonth('fecha', $fechaOperaciones->month)
                  ->whereYear('fecha', $fechaOperaciones->year);
            })
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->whereHas('bolsa', fn ($sq) => $sq->where('nombre', 'like', "%{$s}%")->orWhere('apellidos', 'like', "%{$s}%"))
                    ->orWhereHas('tipoPenalizacion', fn ($sq) => $sq->where('nombre', 'like', "%{$s}%"));
            }))
            ->orderBy('id_tipo_penalizacion')
            ->orderBy('fecha', 'desc');

        $items = $query->get();
        $empleados = Bolsa::when(!empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->with(['cargo:id,nombre,tipo_salario', 'area:id,nombre'])
            ->orderBy('nombre')
            ->get();
        $tipos = CatalogoItem::where('tipo', 'tipos_penalizaciones')
            ->where('activo', true)
            ->select('id', 'nombre', 'extra')
            ->orderBy('nombre')
            ->get();
        $areas = \App\Models\Area::select('id', 'nombre')
            ->when(!empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->orderBy('nombre')
            ->get();
        $pagosAdicionales = CatalogoItem::where('tipo', 'tipos_pagos_adicionales')
            ->where('activo', true)
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        // Enriquecer tipos_penalizaciones con info del pago adicional y área desde extra
        $tiposEnriquecidos = $tipos->map(function ($tipo) {
            $extra = is_array($tipo->extra) ? $tipo->extra : (json_decode($tipo->extra, true) ?? []);
            return [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'porcentaje' => $extra['porcentaje'] ?? null,
                'tipo_pago_adicional_id' => $extra['tipo_pago_adicional_id'] ?? null,
                'area_id' => $extra['area_id'] ?? null,
            ];
        });

        // Agrupar por tipo de penalización
        $agrupadas = $items->groupBy('id_tipo_penalizacion')->map(function ($grupo, $tipoId) use ($tiposEnriquecidos) {
            $tipo = $tiposEnriquecidos->firstWhere('id', $tipoId);
            return [
                'tipo' => $tipo['nombre'] ?? 'Sin tipo',
                'items' => $grupo,
                'total' => $grupo->count(),
            ];
        })->values();

        return Inertia::render('Penalizaciones/Index', [
            'title' => 'Penalizaciones',
            'items' => $items,
            'agrupadas' => $agrupadas,
            'empleados' => $empleados,
            'tiposPenalizaciones' => $tiposEnriquecidos,
            'areas' => $areas,
            'pagosAdicionales' => $pagosAdicionales,
            'filters' => $request->only('search'),
            'fechaOperaciones' => $fechaOperaciones->format('Y-m-d'),
        ]);
    }

    /**
     * Obtener información de un empleado para filtrado en cascada.
     * Retorna: área, cargo, y los pagos adicionales penalizables.
     */
    public function obtenerEmpleado(Request $request)
    {
        $empleado = Bolsa::with(['cargo:id,nombre,tipo_salario', 'area:id,nombre,id_entidad'])
            ->findOrFail($request->id_bolsa);

        $entidadId = $empleado->id_entidad;
        $areaId = $empleado->id_area;

        // Pagos adicionales penalizables (misma entidad del empleado)
        $pagosAdicionales = CatalogoItem::where('tipo', 'tipos_pagos_adicionales')
            ->where('activo', true)
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        // Tipos de penalización disponibles: filtrar por área del empleado y por pago adicional
        // Cada tipo tiene en extra: tipo_pago_adicional_id y area_id
        $tipos = CatalogoItem::where('tipo', 'tipos_penalizaciones')
            ->where('activo', true)
            ->select('id', 'nombre', 'extra')
            ->get()
            ->filter(function ($tipo) use ($areaId) {
                $extra = is_array($tipo->extra) ? $tipo->extra : (json_decode($tipo->extra, true) ?? []);
                // Mostrar tipos cuyo area_id coincida con el área del empleado
                return empty($extra['area_id']) || (int)($extra['area_id']) === (int)$areaId;
            })
            ->values()
            ->map(function ($tipo) {
                $extra = is_array($tipo->extra) ? $tipo->extra : (json_decode($tipo->extra, true) ?? []);
                return [
                    'id' => $tipo->id,
                    'nombre' => $tipo->nombre,
                    'porcentaje' => $extra['porcentaje'] ?? null,
                    'tipo_pago_adicional_id' => $extra['tipo_pago_adicional_id'] ?? null,
                    'area_id' => $extra['area_id'] ?? null,
                ];
            });

        return response()->json([
            'empleado' => [
                'id' => $empleado->id,
                'nombre' => $empleado->nombrecompleto,
                'area' => $empleado->area?->nombre,
                'id_area' => $empleado->id_area,
                'cargo' => $empleado->cargo?->nombre,
                'tipo_salario' => $empleado->cargo?->tipo_salario,
            ],
            'pagosAdicionales' => $pagosAdicionales,
            'tiposPenalizaciones' => $tipos,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Penalizacion::class);
        $data = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tipo_penalizacion' => 'required|exists:catalogo_items,id',
            'id_area_penalizada' => 'nullable|exists:areas,id',
            'id_pago_adicional' => 'nullable|exists:catalogo_items,id',
            'fecha' => 'required|date',
            'importe' => 'required|numeric|min:0|max:100',
        ]);

        $this->autorizarEntidad(Bolsa::find($data['id_bolsa'])?->id_entidad);

        Penalizacion::create($data);

        return redirect()->back()->with('success', 'Penalización registrada correctamente.');
    }

    public function update(Request $request, Penalizacion $penalizacion)
    {
        $this->authorize('update', $penalizacion);
        $this->autorizarEntidad($penalizacion->bolsa?->id_entidad);

        $data = $request->validate([
            'id_bolsa' => 'required|exists:bolsa,id',
            'id_tipo_penalizacion' => 'required|exists:catalogo_items,id',
            'id_area_penalizada' => 'nullable|exists:areas,id',
            'id_pago_adicional' => 'nullable|exists:catalogo_items,id',
            'fecha' => 'required|date',
            'importe' => 'required|numeric|min:0|max:100',
        ]);

        $penalizacion->update($data);

        return redirect()->back()->with('success', 'Penalización actualizada correctamente.');
    }

    public function destroy(Penalizacion $penalizacion)
    {
        $this->authorize('delete', $penalizacion);
        $this->autorizarEntidad($penalizacion->bolsa?->id_entidad);

        $penalizacion->delete();

        return redirect()->back()->with('success', 'Penalización eliminada correctamente.');
    }
}
