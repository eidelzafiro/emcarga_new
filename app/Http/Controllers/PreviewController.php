<?php

namespace App\Http\Controllers;

use App\Models\HojasRuta;
use App\Models\SolicitudesServicio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Vistas de prueba (proof-of-concept) del formato de tarjetas aplicado a
 * Hojas de Ruta y Solicitudes. Solo lectura — sin acciones CRUD.
 */
class PreviewController extends Controller
{
    public function hojasRuta(Request $request)
    {
        $entidadId = session('entidad_activa_id');
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $inicioMes = Carbon::parse($fechaOperaciones)->startOfMonth()->toDateString();
        $finMes = Carbon::parse($fechaOperaciones)->endOfMonth()->toDateString();

        $hojas = HojasRuta::with(['tractivo:id,codigo,id_entidad,id_grupo,indice_consumo', 'arrastre:id,codigo', 'chofer:id,nombre,apellidos', 'chofer2:id,nombre,apellidos', 'entidad:id,nombre', 'parqueo:id,nombre', 'grupo:id,nombre'])
            ->withCount(['cartasPorte' => fn ($q) => $q->where('estado', '!=', 'cancelada')])
            ->when($entidadId, fn ($q) => $q->whereHas('tractivo', fn ($t) => $t->where('id_entidad', $entidadId)))
            ->where(fn ($q) => $q->whereNull('fecha_cierre')->orWhereBetween('fecha_cierre', [$inicioMes, $finMes]))
            ->orderByDesc('fecha_emision')
            ->limit(12)
            ->get();

        return Inertia::render('Previews/HojasRutaPreview', [
            'title' => 'Vista previa · Hoja de Ruta',
            'hojas' => $hojas,
        ]);
    }

    public function solicitudes(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');

        $solicitudes = SolicitudesServicio::with([
            'cliente:id,nombre',
            'lugarOrigen:id,nombre',
            'lugarDestino:id,nombre',
            'producto:id,codigo,nombre',
            'producto2:id,codigo,nombre',
            'tipoCarga:id,codigo,nombre',
            'tipoCarga2:id,codigo,nombre',
            'moneda:id,codigo,nombre,simbolo',
            'cartasPorte' => fn ($q) => $q->where('estado', '!=', 'cancelada'),
        ])
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->orderBy('fecha_planificada', 'asc')
            ->limit(12)
            ->get();

        foreach ($solicitudes as $sol) {
            $total = (float) ($sol->peso1 ?? 0) + (float) ($sol->peso2 ?? 0);
            $ejecutado = (float) $sol->cartasPorte->sum('ingreso_mt');
            $sol->toneladas_total = $total;
            $sol->toneladas_ejecutadas = $ejecutado;
            $sol->toneladas_pendientes = max(0, $total - $ejecutado);
            $sol->estado_cumplimiento = match (true) {
                $ejecutado <= 0 => 'pendiente',
                $sol->toneladas_pendientes > 0 => 'en_proceso',
                default => 'realizada',
            };
        }

        return Inertia::render('Previews/SolicitudesPreview', [
            'title' => 'Vista previa · Solicitudes',
            'solicitudes' => $solicitudes,
        ]);
    }
}
