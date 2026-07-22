<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;
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
        return Inertia::render('Auth/Login');
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
        ], [
            'username.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        // Los usernames se guardan en mayúsculas (paridad con el legacy)
        $user = User::where('username', strtoupper($credenciales['username']))->first();

        // Mensaje genérico para no revelar si el usuario existe
        if (! $user) {
            Bitacora::registrar('login_fallido', 'Usuario inexistente: '.$credenciales['username']);

            throw ValidationException::withMessages([
                'username' => 'Credenciales no válidas.',
            ]);
        }

        if ($user->estaBloqueado()) {
            Bitacora::registrar('login_bloqueado', 'Intento de acceso con usuario bloqueado.', $user->id);

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

            throw ValidationException::withMessages([
                'username' => 'Credenciales no válidas.',
            ]);
        }

        // Acceso concedido: reiniciar contador y registrar el acceso
        $user->update(['intentos_fallidos' => 0, 'ultimo_login' => now()]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

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
