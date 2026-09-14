<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Autenticación de la API móvil (Sanctum).
 *
 * Replica la lógica del login web: username en MAYÚSCULAS, bloqueo por
 * intentos, mensaje genérico anti-enumeración, bitácora y fecha de operaciones.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credenciales = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [
            'username.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $throttleKey = 'api-login:'.strtolower($credenciales['username']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Demasiados intentos de inicio de sesión. Intente nuevamente en {$seconds} segundos.",
            ]);
        }

        // Los usernames se guardan en mayúsculas (paridad con el legacy).
        $user = User::where('username', strtoupper($credenciales['username']))->first();

        if (! $user || $user->estaBloqueado() || ! Hash::check($credenciales['password'], $user->password)) {
            if ($user && ! $user->estaBloqueado()) {
                $user->increment('intentos_fallidos');
                Bitacora::registrar('login_fallido', 'API: contraseña incorrecta (intento '.$user->intentos_fallidos.').', $user->id);
            } else {
                Bitacora::registrar('login_fallido', 'API: usuario inexistente o bloqueado.');
            }

            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'username' => 'Credenciales no válidas.',
            ]);
        }

        $user->update(['intentos_fallidos' => 0, 'ultimo_login' => now()]);
        RateLimiter::clear($throttleKey);

        // Abilities del token: entidad activa, perfil y mes de operaciones.
        $entidad = (int) ($user->id_entidad ?? 0);
        $perfil = $user->getRoleNames()->first() ?? 'SIN_PERFIL';
        $fecha = now()->format('Y-m');

        $abilities = array_filter([
            $entidad ? "entidad:{$entidad}" : null,
            "perfil:{$perfil}",
            "fecha:{$fecha}",
        ]);

        $token = $user->createToken($credenciales['device_name'] ?? 'mobile', array_values($abilities))->plainTextToken;

        Bitacora::registrar('login_api', 'Inicio de sesión API.', $user->id);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'entidades' => $user->entidadesAcceso()
                ->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre, 'abreviatura' => $e->abreviatura ?? $e->nombre])
                ->values(),
            'password_temporal' => (bool) $user->password_temporal,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => new UserResource($user),
            'perfil_activo' => $user->getRoleNames()->first(),
            'entidad_activa' => $request->attributes->get('api_entidad_id'),
            'fecha_operaciones' => $request->attributes->get('api_fecha_operaciones'),
            'permisos' => $user->getAllPermissions()->pluck('name'),
            'entidades' => $user->entidadesAcceso()
                ->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre, 'abreviatura' => $e->abreviatura ?? $e->nombre])
                ->values(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        Bitacora::registrar('logout_api', 'Cierre de sesión API.', $request->user()->id);

        return response()->json(['ok' => true]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        Bitacora::registrar('logout_api', 'Cierre de todas las sesiones API.', $request->user()->id);

        return response()->json(['ok' => true]);
    }
}
