<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

/**
 * Recuperación de contraseña (auto-servicio, usuarios invitados).
 *
 * Solo aplica a usuarios con correo guardado. El envío se enruta por
 * dominio: ".cu" → SMTP nacional; resto → Gmail (MailRouter). La respuesta
 * es siempre genérica para no revelar si el correo existe o no.
 */
class ForgotPasswordController extends Controller
{
    public function solicitarForm()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function enviarEnlace(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:191'],
        ]);

        $user = User::where('email', trim($request->input('email')))->first();

        if ($user && ! empty($user->email)) {
            // Respuesta del broker: siempre status/reset genérico al usuario
            // aunque el envío falle silenciosamente (sin credenciales cae a log).
            Password::broker()->sendResetLink(
                ['email' => $user->email]
            );
        }

        return back()->with('status', __(
            'Si el correo está registrado en el sistema, recibirá un enlace de restablecimiento en unos minutos.'
        ));
    }

    public function formularioReset(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function guardarReset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                DB::transaction(function () use ($user, $password) {
                    $user->forceFill([
                        'password' => bcrypt($password),
                        // El password ya no es temporal: el usuario demostró control
                        // del correo registrado.
                        'password_temporal' => false,
                        'intentos_fallidos' => 0,
                        'bloqueado' => false,
                    ])->save();
                });
            }
        );

        if ($estado === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', __('Contraseña restablecida correctamente. Ya puede iniciar sesión.'));
        }

        return back()->withErrors(['email' => [__($estado)]]);
    }
}
