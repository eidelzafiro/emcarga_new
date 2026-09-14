<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\ScopesEntidadApi;
use App\Http\Controllers\Controller;
use App\Models\ApiSyncLog;
use App\Models\Lugare;
use App\Models\TipoEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sincronización offline-first (Fase 8). Entrega al cliente móvil el paquete de
 * datos de referencia (catálogos) y registra la operación en `api_sync_log`.
 *
 * La sincronización es "pull" (el cliente cachea lecturas); las escrituras
 * críticas se hacen online. No hay resolución de conflictos en esta fase.
 */
class SyncController extends Controller
{
    use ScopesEntidadApi;

    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
        ]);

        $lugares = Lugare::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'provincia', 'municipio', 'latitud', 'longitud']);

        $tiposEquipo = TipoEquipo::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $items = $lugares->count() + $tiposEquipo->count();

        $entidad = $this->entidadActivaId($request);
        $fecha = $this->fechaOperaciones($request);

        ApiSyncLog::create([
            'user_id' => $request->user()->id,
            'direction' => 'pull',
            'endpoint' => 'sync/pull',
            'id_entidad' => $entidad ?: null,
            'fecha_operaciones' => $fecha->toDateString(),
            'items' => $items,
            'payload_hash' => hash('sha256', $lugares->toJson().$tiposEquipo->toJson()),
        ]);

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'entidad_activa' => $entidad,
            'fecha_operaciones' => $fecha->toDateString(),
            'items' => $items,
            'catalogos' => [
                'lugares' => $lugares,
                'tipos_equipo' => $tiposEquipo,
            ],
        ]);
    }
}
