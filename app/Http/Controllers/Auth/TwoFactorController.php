<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactor,
    ) {}

    /**
     * Pantalla del challenge (login diferido). Requiere sesión two_factor_pending.
     */
    public function create(Request $request)
    {
        if (! $request->session()->has('two_factor_pending')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactor', [
            'title' => 'Verificación en dos pasos',
        ]);
    }

    /**
     * Verifica el código y completa el login diferido.
     */
    public function store(Request $request)
    {
        $pending = $request->session()->get('two_factor_pending');
        if (! $pending) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ], [
            'code.required' => 'El código es obligatorio.',
        ]);

        $user = User::find($pending['user_id']);
        if (! $user) {
            $request->session()->forget('two_factor_pending');

            return redirect()->route('login');
        }

        $code = $request->input('code');
        $valido = $this->twoFactor->verify($user->two_factor_secret, $code)
            || $this->twoFactor->verifyRecovery($user, $code);

        if (! $valido) {
            Bitacora::registrar('login_2fa_fallido', 'Código 2FA incorrecto.', $user->id);

            throw ValidationException::withMessages([
                'code' => 'Código no válido.',
            ]);
        }

        // Login real (el password ya fue validado en LoginController).
        Auth::login($user, $pending['remember'] ?? false);
        $request->session()->regenerate();
        $request->session()->forget('two_factor_pending');
        $request->session()->put('fecha_operaciones', $pending['fecha_operaciones']);

        Bitacora::registrar('login', 'Inicio de sesión exitoso (2FA).', $user->id);

        if ($user->password_temporal) {
            return redirect()->route('password.edit')
                ->with('warning', 'Debe cambiar su contraseña temporal antes de continuar.');
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Inicia la habilitación de 2FA: genera secreto + códigos de recuperación y
     * los devuelve para que el usuario los confirme con un código del autenticador.
     * Solo para usuarios privilegiados.
     */
    public function enable(Request $request)
    {
        $user = $request->user();
        if (! $user->esPrivilegiado()) {
            abort(403);
        }

        $secret = $this->twoFactor->generateSecret();
        $recovery = $this->twoFactor->generateRecoveryCodes();

        $request->session()->put('two_factor_setup', [
            'secret' => $secret,
            'recovery' => $recovery,
        ]);

        return Inertia::render('Profile/TwoFactor', [
            'otpauth' => $this->twoFactor->otpauthUri($user, $secret),
            'secret' => $secret,
            'recovery' => $recovery,
            'enabled' => (bool) $user->two_factor_enabled,
        ]);
    }

    /**
     * Confirma la habilitación: el código introducido debe coincidir con el
     * secreto recién generado; entonces se persiste.
     */
    public function confirm(Request $request)
    {
        $user = $request->user();
        $setup = $request->session()->get('two_factor_setup');

        if (! $setup) {
            return redirect()->route('two-factor.enable');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $this->twoFactor->verify($setup['secret'], $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'El código no coincide. Reintente escanear el código QR.',
            ]);
        }

        $user->update([
            'two_factor_secret' => $setup['secret'],
            'two_factor_recovery' => $setup['recovery'],
        ]);
        $request->session()->forget('two_factor_setup');

        Bitacora::registrar('2fa_habilitado', '2FA habilitado para el usuario.', $user->id);

        return redirect()->route('two-factor.enable')
            ->with('success', 'Verificación en dos pasos activada.');
    }

    /**
     * Deshabilita 2FA para el usuario autenticado.
     */
    public function disable(Request $request)
    {
        $user = $request->user();
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery' => null,
        ]);

        Bitacora::registrar('2fa_deshabilitado', '2FA deshabilitado para el usuario.', $user->id);

        return redirect()->route('two-factor.enable')
            ->with('success', 'Verificación en dos pasos desactivada.');
    }
}
