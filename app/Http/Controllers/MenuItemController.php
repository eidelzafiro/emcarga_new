<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\Bitacora;
use App\Models\MenuItem;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuItemController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', MenuItem::class);

        $items = MenuItem::with('children')
            ->whereNull('parent_id')
            ->orderBy('orden')
            ->get()
            ->map(fn (MenuItem $item) => $this->mapNode($item));

        $permisos = Permission::orderBy('name')->pluck('name');

        $roles = Role::with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]);

        return Inertia::render('MenuItems/Index', [
            'title' => 'Gestión del menú',
            'items' => $items,
            'permisos' => $permisos,
            'roles' => $roles,
            'parents' => MenuItem::orderBy('label')->get(['id', 'label', 'parent_id']),
        ]);
    }

    public function store(StoreMenuItemRequest $request)
    {
        $this->authorize('create', MenuItem::class);
        $datos = $request->validated();

        $item = MenuItem::create($datos);

        Bitacora::registrar('crear_menu', "Ítem de menú {$item->label} creado.");

        return redirect()->route('menu-items.index')
            ->with('success', 'Ítem de menú creado correctamente.');
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem)
    {
        $this->authorize('update', $menuItem);
        $datos = $request->validated();

        $menuItem->update($datos);

        Bitacora::registrar('editar_menu', "Ítem de menú {$menuItem->label} actualizado.");

        return redirect()->route('menu-items.index')
            ->with('success', 'Ítem de menú actualizado correctamente.');
    }

    public function destroy(MenuItem $menuItem)
    {
        $this->authorize('delete', $menuItem);

        if ($menuItem->children()->exists()) {
            return back()->with('error', 'No se puede eliminar un agrupador con hijos. Reasigne o elimine los hijos primero.');
        }

        $label = $menuItem->label;
        $menuItem->delete();

        Bitacora::registrar('eliminar_menu', "Ítem de menú {$label} eliminado.");

        return redirect()->route('menu-items.index')
            ->with('success', 'Ítem de menú eliminado correctamente.');
    }

    public function toggleVisibility(MenuItem $menuItem, Role $role)
    {
        $this->authorize('update', $menuItem);

        $permiso = $menuItem->permission;
        if (!$permiso) {
            return back()->with('warning', 'Este ítem no tiene permiso asociado. Asígnese un permiso para controlar su visibilidad.');
        }

        if ($role->hasPermissionTo($permiso)) {
            $role->revokePermissionTo($permiso);
            Bitacora::registrar('ocultar_menu', "Ítem {$menuItem->label} ocultado del rol {$role->name}.");
            return back()->with('success', "Ítem ocultado del rol {$role->name}.");
        } else {
            $role->givePermissionTo($permiso);
            Bitacora::registrar('mostrar_menu', "Ítem {$menuItem->label} mostrado al rol {$role->name}.");
            return back()->with('success', "Ítem mostrado al rol {$role->name}.");
        }
    }

    private function mapNode(MenuItem $item): array
    {
        $hijos = $item->children
            ->where('activo', true)
            ->sortBy('orden')
            ->values()
            ->map(fn (MenuItem $hijo) => $this->mapNode($hijo));

        return [
            'id' => $item->id,
            'label' => $item->label,
            'icon' => $item->icon,
            'route' => $item->route,
            'permission' => $item->permission,
            'orden' => $item->orden,
            'activo' => $item->activo,
            'parent_id' => $item->parent_id,
            'children' => $hijos,
        ];
    }
}
