<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Rules\PasswordFuerte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PasswordController extends Controller
{
    /**
     * Muestra el formulario de cambio de contraseña.
     */
    public function edit()
    {
        return Inertia::render('Auth/ChangePassword', [
            'title' => 'Cambiar contraseña',
        ]);
    }

    /**
     * Actualiza la contraseña aplicando las reglas del sistema legacy:
     * fortaleza, no reutilizar contraseñas del histórico (cod_usuariosh)
     * y registro del cambio en bitácora.
     */
    public function update(Request $request)
    {
        $request->validate([
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:password_actual', new PasswordFuerte],
        ], [
            'password_actual.required' => 'Debe indicar su contraseña actual.',
            'password_actual.current_password' => 'La contraseña actual no es válida.',
            'password.required' => 'Debe indicar la nueva contraseña.',
            'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
            'password.different' => 'La nueva contraseña no puede ser igual a la anterior.',
        ]);

        $user = $request->user();

        // Verificar que la contraseña no se haya utilizado antes (histórico legacy)
        $reutilizada = $user->passwordHistories()->get()
            ->contains(fn ($historia) => Hash::check($request->password, $historia->password));

        if ($reutilizada) {
            throw ValidationException::withMessages([
                'password' => 'La contraseña ya fue utilizada anteriormente. Elija una diferente.',
            ]);
        }

        // Guardar la contraseña anterior en el histórico antes de reemplazarla
        $user->passwordHistories()->create([
            'password' => $user->getAuthPassword(),
            'fecha_cambio' => now(),
        ]);

        $user->update([
            'password' => $request->password, // el cast 'hashed' aplica bcrypt
            'fecha_cambio_password' => now(),
            'password_temporal' => false,
        ]);

        Bitacora::registrar('cambio_password', 'El usuario cambió su contraseña.');

        return redirect()->route('dashboard')
            ->with('success', 'Contraseña actualizada correctamente.');
    }
}
