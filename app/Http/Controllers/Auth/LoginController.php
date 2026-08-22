<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    /**
     * Muestra el formulario de login.
     */
    public function create()
    {
        return Inertia::render('Auth/Login', [
            'title' => 'Iniciar sesión',
        ]);
    }

    /**
     * Autentica al usuario aplicando las reglas del sistema legacy:
     * bloqueo por intentos fallidos, registro en bitácora y
     * redirección a cambio de contraseña temporal.
     */
    public function store(Request $request)
    {
        $credenciales = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'fecha_operaciones' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'username.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'fecha_operaciones.date_format' => 'La fecha de operaciones no es válida.',
        ]);

        // Throttle basado en tiempo/IP: evita fuerza bruta y abuso del bloqueo
        // por cuenta (que de lo contrario permitiría bloquear a cualquier
        // usuario con solo 5 intentos fallidos). La clave combina el usuario
        // intentado y la IP para limitar intentos por ambas dimensiones.
        $throttleKey = 'login:'.strtolower($credenciales['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Demasiados intentos de inicio de sesión. Intente nuevamente en {$seconds} segundos.",
            ]);
        }

        // Los usernames se guardan en mayúsculas (paridad con el legacy)
        $user = User::where('username', strtoupper($credenciales['username']))->first();

        // Mensaje genérico para no revelar si el usuario existe
        if (! $user) {
            Bitacora::registrar('login_fallido', 'Usuario inexistente: '.$credenciales['username']);
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'username' => 'Credenciales no válidas.',
            ]);
        }

        if ($user->estaBloqueado()) {
            Bitacora::registrar('login_bloqueado', 'Intento de acceso con usuario bloqueado.', $user->id);
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'username' => 'El usuario se encuentra bloqueado. Contacte con el administrador del sistema.',
            ]);
        }

        if (! Hash::check($credenciales['password'], $user->password)) {
            $user->intentos_fallidos++;
            $user->save();

            Bitacora::registrar('login_fallido', 'Contraseña incorrecta (intento '.$user->intentos_fallidos.').', $user->id);

            if ($user->intentos_fallidos >= User::MAX_INTENTOS_LOGIN) {
                Bitacora::registrar('bloqueo_automatico', 'Bloqueado tras '.User::MAX_INTENTOS_LOGIN.' intentos fallidos.', $user->id);
            }

            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'username' => 'Credenciales no válidas.',
            ]);
        }

        // Acceso concedido: reiniciar contador y registrar el acceso
        $user->update(['intentos_fallidos' => 0, 'ultimo_login' => now()]);

        // Fecha de operaciones: la elegida en el login (o la de hoy).
        // Se persiste en el usuario (paridad con cod_usuarios.foperaciones).
        $fechaOperaciones = $credenciales['fecha_operaciones'] ?? now()->toDateString();
        if ($user->fecha_operaciones?->toDateString() !== $fechaOperaciones) {
            $user->update(['fecha_operaciones' => $fechaOperaciones]);
        }

        Auth::login($user, $request->boolean('remember'));
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        // Contexto de trabajo en sesión (el middleware EstablecerContextoTrabajo
        // completa la entidad activa en el siguiente request)
        $request->session()->put('fecha_operaciones', $fechaOperaciones);

        // 2FA para perfiles privilegiados: si tiene 2FA habilitado, diferir el
        // login real hasta verificar el código en el challenge.
        if ($user->esPrivilegiado() && $user->two_factor_enabled) {
            $request->session()->put('two_factor_pending', [
                'user_id' => $user->id,
                'remember' => $request->boolean('remember'),
                'fecha_operaciones' => $fechaOperaciones,
            ]);
            Bitacora::registrar('login_2fa_pendiente', 'Acceso concedido (pendiente 2FA).', $user->id);

            return redirect()->route('two-factor.create');
        }

        Bitacora::registrar('login', 'Inicio de sesión exitoso.', $user->id);

        if ($user->password_temporal) {
            return redirect()->route('password.edit')
                ->with('warning', 'Debe cambiar su contraseña temporal antes de continuar.');
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function destroy(Request $request)
    {
        Bitacora::registrar('logout', 'Cierre de sesión.');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
