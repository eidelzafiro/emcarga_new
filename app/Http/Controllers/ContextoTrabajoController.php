<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Entidad;
use Illuminate\Http\Request;

/**
 * Contexto de trabajo del usuario: entidad activa y fecha de operaciones.
 * Ambos valores se guardan en sesión y la fecha se persiste además
 * en el usuario (paridad con cod_usuarios.foperaciones del legacy).
 */
class ContextoTrabajoController extends Controller
{
    /**
     * Cambia la entidad activa del usuario (solo entre las permitidas).
     */
    public function cambiarEntidad(Request $request)
    {
        $datos = $request->validate([
            'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
        ]);

        $user = $request->user();

        if (! $user->tieneAccesoAEntidad((int) $datos['entidad_id'])) {
            return back()->with('error', 'No tiene acceso a la entidad seleccionada.');
        }

        $request->session()->put('entidad_activa_id', (int) $datos['entidad_id']);

        $entidad = Entidad::find($datos['entidad_id']);
        Bitacora::registrar('cambiar_entidad', "Entidad activa: {$entidad?->abreviatura}", $user->id);

        return back()->with('success', 'Entidad activa actualizada.');
    }

    /**
     * Cambia la fecha de operaciones (sesión + persistencia en el usuario).
     */
    public function cambiarFechaOperaciones(Request $request)
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $request->session()->put('fecha_operaciones', $datos['fecha']);
        $request->user()->update(['fecha_operaciones' => $datos['fecha']]);

        return back()->with('success', 'Fecha de operaciones actualizada.');
    }
}
