<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePerfilRequest;
use App\Http\Requests\UpdatePerfilRequest;
use App\Models\Bitacora;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PerfilController extends Controller
{
    /**
     * Perfiles protegidos del sistema: no se pueden eliminar ni renombrar.
     */
    private const PERFILES_PROTEGIDOS = ['ADMIN', 'SUPERADMIN', 'ADMINISTRADOR'];

    /**
     * Lista los perfiles con sus permisos y cantidad de usuarios.
     */
    public function index()
    {
        $this->authorize('viewAny', Role::class);

        return Inertia::render('Perfiles/Index', [
            'title' => 'Gestión de perfiles',
            'perfiles' => Role::with('permissions:id,name')
                ->withCount('users')
                ->orderBy('name')
                ->get(),
            'permisos' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Crea un perfil nuevo con sus permisos asignados.
     */
    public function store(StorePerfilRequest $request)
    {
        $this->authorize('create', Role::class);
        $datos = $request->validated();

        $perfil = Role::create(['name' => $datos['nombre']]);
        $perfil->syncPermissions($datos['permisos'] ?? []);

        Bitacora::registrar('crear_perfil', "Perfil {$perfil->name} creado con ".count($datos['permisos'] ?? []).' permisos.');

        return redirect()->route('perfiles.index')
            ->with('success', 'Perfil creado correctamente.');
    }

    /**
     * Actualiza el nombre y los permisos de un perfil.
     * ADMIN no puede renombrarse (los seeders y la ETL lo referencian).
     */
    public function update(UpdatePerfilRequest $request, Role $perfil)
    {
        $this->authorize('update', $perfil);
        $datos = $request->validated();

        if (in_array($perfil->name, self::PERFILES_PROTEGIDOS) && $datos['nombre'] !== $perfil->name) {
            return back()->with('error', 'No se puede renombrar el perfil '.$perfil->name.'.');
        }

        $perfil->update(['name' => $datos['nombre']]);
        $perfil->syncPermissions($datos['permisos'] ?? []);

        Bitacora::registrar('editar_perfil', "Perfil {$perfil->name} actualizado con ".count($datos['permisos'] ?? []).' permisos.');

        return redirect()->route('perfiles.index')
            ->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Elimina un perfil. Protecciones: ADMIN no se elimina y tampoco
     * un perfil que tenga usuarios asignados.
     */
    public function destroy(Role $perfil)
    {
        $this->authorize('delete', $perfil);

        if (in_array($perfil->name, self::PERFILES_PROTEGIDOS)) {
            return back()->with('error', 'No se puede eliminar el perfil '.$perfil->name.'.');
        }

        if ($perfil->users()->exists()) {
            return back()->with('error', "El perfil {$perfil->name} tiene usuarios asignados. Reasígnelos antes de eliminarlo.");
        }

        $nombre = $perfil->name;
        $perfil->delete();

        Bitacora::registrar('eliminar_perfil', "Perfil {$nombre} eliminado.");

        return redirect()->route('perfiles.index')
            ->with('success', 'Perfil eliminado correctamente.');
    }
}
