<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Contexto de trabajo de la API móvil.
 *
 * Permite fijar la entidad activa y el mes de operaciones editando las
 * abilities del token Sanctum actual (stateless). El resto de endpoints leen
 * esas abilities vía ResolverEntidadApi / ResolverFechaOperacionesApi.
 */
class ContextoController extends Controller
{
    public function entidad(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_entidad' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $permitidas = $user->entidadesAcceso()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (! in_array((int) $data['id_entidad'], $permitidas, true)) {
            abort(403, 'No tiene acceso a esa entidad.');
        }

        $this->reemplazarAbility($request, 'entidad:', 'entidad:'.(int) $data['id_entidad']);

        return response()->json([
            'ok' => true,
            'entidad_activa' => (int) $data['id_entidad'],
        ]);
    }

    public function fecha(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m'],
        ]);

        $fecha = Carbon::createFromFormat('Y-m', $data['fecha'])->startOfMonth();

        $this->reemplazarAbility($request, 'fecha:', 'fecha:'.$fecha->format('Y-m'));

        return response()->json([
            'ok' => true,
            'fecha_operaciones' => $fecha->toDateString(),
        ]);
    }

    /**
     * Reemplaza la ability con el prefijo dado en el token actual (conserva el
     * resto). El token se persiste para que el cambio sobreviva a la petición.
     */
    private function reemplazarAbility(Request $request, string $prefijo, string $nueva): void
    {
        $token = $request->user()->currentAccessToken();

        if (! $token) {
            return;
        }

        $abilities = collect($token->abilities ?? [])
            ->reject(fn ($a) => str_starts_with((string) $a, $prefijo))
            ->push($nueva)
            ->unique()
            ->values()
            ->all();

        $token->forceFill(['abilities' => $abilities])->save();
    }
}
