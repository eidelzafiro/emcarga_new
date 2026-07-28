<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Bitacora;
use App\Models\Entidad;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Lista los usuarios con búsqueda y paginación.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $usuarios = User::with('roles:id,name', 'entidades:id,nombre', 'entidad:id,nombre,abreviatura')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $user = $request->user();
        $entidadesQuery = Entidad::where('activo', true);

        if (! $user->hasAnyRole(['SUPERADMIN', 'CONFIGURACIONES'])) {
            $ids = collect(Entidad::subEntidadesIds($user->id_entidad))
                ->push($user->id_entidad)
                ->unique()
                ->values()
                ->all();
            $adicionales = $user->entidades()->pluck('entidades.id')->all();
            $ids = array_unique([...$ids, ...$adicionales]);
            $entidadesQuery->whereIn('id', $ids);
        }

        return Inertia::render('Usuarios/Index', [
            'title' => 'Gestión de usuarios',
            'usuarios' => $usuarios,
            'roles' => Role::orderBy('name')->pluck('name'),
            'entidades' => $entidadesQuery->orderBy('nombre')->get(['id', 'nombre', 'parent_id']),
            'esAdmin' => $user->hasAnyRole(['SUPERADMIN', 'CONFIGURACIONES']),
            'miEntidadId' => $user->id_entidad,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Crea un usuario con contraseña temporal (debe cambiarla en su
     * primer acceso). El username se guarda en mayúsculas (legacy).
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);
        $datos = $request->validated();

        $user = User::create([
            'name' => $datos['name'],
            'username' => strtoupper($datos['username']),
            'email' => $datos['email'] ?? null,
            'password' => $datos['password'],
            'id_entidad' => $datos['id_entidad'] ?: $request->user()->id_entidad,
            'idgrupo' => $datos['idgrupo'] ?? null,
            'password_temporal' => true,
        ]);
        $user->assignRole($datos['role']);
        $user->entidades()->sync($datos['entidades'] ?? []);

        Bitacora::registrar('crear_usuario', "Usuario {$user->username} creado con perfil {$datos['role']}.");

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Actualiza los datos y el perfil de un usuario.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $datos = $request->validated();

        $payload = [
            'name' => $datos['name'],
            'username' => strtoupper($datos['username']),
            'email' => $datos['email'] ?? null,
            'idgrupo' => $datos['idgrupo'] ?? null,
        ];

        if ($request->user()->hasAnyRole(['SUPERADMIN', 'CONFIGURACIONES'])) {
            $payload['id_entidad'] = $datos['id_entidad'];
        }

        $user->update($payload);
        $user->syncRoles([$datos['role']]);
        $user->entidades()->sync($datos['entidades'] ?? []);

        Bitacora::registrar('editar_usuario', "Usuario {$user->username} actualizado (perfil {$datos['role']}).");

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Elimina un usuario (soft delete). No permite auto-eliminarse.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puede eliminar su propio usuario.');
        }

        $username = $user->username;
        $user->delete();

        Bitacora::registrar('eliminar_usuario', "Usuario {$username} eliminado.");

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }

    /**
     * Desbloquea un usuario: limpia el bloqueo manual y el contador
     * de intentos fallidos (legacy: desbloquear pone bloqueado = 0).
     */
    public function desbloquear(User $user)
    {
        $this->authorize('desbloquear', $user);

        $user->update(['bloqueado' => false, 'intentos_fallidos' => 0]);

        Bitacora::registrar('desbloquear_usuario', "Usuario {$user->username} desbloqueado.");

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario desbloqueado correctamente.');
    }

    /**
     * Restablece la contraseña con una temporal indicada por el
     * administrador (el legacy usaba la fija 'ZAFIRO'). El usuario
     * deberá cambiarla en su próximo acceso. También lo desbloquea,
     * caso frecuente tras un bloqueo por intentos.
     */
    public function restablecerPassword(Request $request, User $user)
    {
        $this->authorize('restablecerPassword', $user);

        $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ], [
            'password.required' => 'Debe indicar la contraseña temporal.',
            'password.min' => 'La contraseña temporal debe tener al menos 6 caracteres.',
        ]);

        $user->update([
            'password' => $request->password,
            'password_temporal' => true,
            'intentos_fallidos' => 0,
            'bloqueado' => false,
        ]);

        Bitacora::registrar('restablecer_password', "Contraseña restablecida para {$user->username}.");

        return redirect()->route('usuarios.index')
            ->with('success', 'Contraseña restablecida. El usuario deberá cambiarla en su próximo acceso.');
    }
}
