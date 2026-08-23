<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Perfil auto-gestionable: nombre, apellidos, correo y avatar.
 * El username NO es editable (es el identificador de acceso, paridad legacy).
 */
class ProfileController extends Controller
{
    /**
     * Formulario de edición del perfil propio.
     */
    public function edit(Request $request)
    {
        return Inertia::render('Profile/Edit', [
            'title' => 'Mi perfil',
        ]);
    }

    /**
     * Actualiza los datos personales del usuario autenticado.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'apellidos' => ['nullable', 'string', 'max:190'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email,'.$user->id],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.unique' => 'Ese correo ya está en uso por otro usuario.',
            'email.email' => 'El formato del correo no es válido.',
        ]);

        $correoAnterior = $user->email;

        $user->update($datos);

        if ($correoAnterior !== $user->email) {
            Bitacora::registrar('perfil_correo',
                "El usuario cambió su correo de {$correoAnterior} a {$user->email}.");
        }

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Sube y reemplaza el avatar (imagen cuadrada recomendada).
     */
    public function actualizarAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width:1024,max_height:1024'],
        ], [
            'avatar.image' => 'El archivo debe ser una imagen.',
            'avatar.mimes' => 'Formatos permitidos: JPG, PNG o WEBP.',
            'avatar.max' => 'La imagen no puede superar 2 MB.',
            'avatar.dimensions' => 'La imagen no puede superar 1024x1024 píxeles.',
        ]);

        $user = $request->user();

        // Reemplazo: elimina el avatar anterior antes de guardar el nuevo.
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $ruta = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $ruta]);

        Bitacora::registrar('perfil_avatar', 'El usuario actualizó su avatar.');

        return back()->with('success', 'Avatar actualizado correctamente.');
    }

    /**
     * Elimina el avatar (vuelve a mostrarse las iniciales).
     */
    public function eliminarAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return back()->with('success', 'Avatar eliminado.');
    }
}
