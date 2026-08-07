<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\Bitacora;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuItemController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', MenuItem::class);

        return $this->respuestaIndex();
    }

/**
     * Reordena el árbol de menú tras el arrastrar y soltar.
     *
     * Recibe el árbol en el orden visual deseado (padres con sus hijos en
     * profundidad). Respeta exactamente el orden dejado por el usuario con el
     * arrastrar y soltar: no se reordena alfabéticamente ni se fuerzan
     * posiciones fijas; solo se renumeran los ítems de forma consecutiva
     * (1..n) dentro de cada grupo, conservando el parent_id enviado.
     */
    public function reordenar(Request $request)
    {
        $this->authorize('update', new MenuItem());

        $data = $request->validate([
            'tree' => ['required', 'array'],
            'tree.*.id' => ['required', 'integer', 'exists:menu_items,id'],
            'tree.*.parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'tree.*.children' => ['present', 'array'],
            'tree.*.children.*.id' => ['required', 'integer', 'exists:menu_items,id'],
        ]);

        // Ítems actuales para consultar label/route de cada nodo.
        $modelos = MenuItem::query()
            ->get(['id', 'parent_id', 'label', 'route', 'permission', 'orden', 'icon', 'activo'])
            ->keyBy('id');

        // Aplana el árbol anidado (raíces con sus children) a [id, parent].
        $nodos = $this->flattenArbol($data['tree']);

        DB::transaction(function () use ($nodos, $modelos) {
            // parent_id => lista ordenada de ids según el orden visual dado.
            // La raíz se guarda con la clave 'root' (PHP convierte null a '' en claves).
            $padres = [];
            foreach ($nodos as $nodo) {
                $clave = $nodo['parent'] === null ? 'root' : $nodo['parent'];
                $padres[$clave][] = $nodo['id'];
            }

            // Aplicar parent_id y renumerar 1..n por grupo, respetando el orden
            // del arrastrar y soltar (no se reordena alfabéticamente).
            foreach ($padres as $parentId => $ids) {
                $orden = 1;
                foreach ($ids as $id) {
                    $item = $modelos->get($id);
                    if (! $item) {
                        continue;
                    }
                    $item->parent_id = $parentId === 'root' ? null : $parentId;
                    $item->orden = $orden++;
                    $item->save();
                }
            }

            Bitacora::registrar('reordenar_menu', 'Menú reordenado.');
        });

        return $this->respuestaIndex('El menú se ha reordenado correctamente.');
    }

    /**
     * Aplana un árbol anidado {id, children} a una lista [id, parent].
     */
    private function flattenArbol(array $raices): array
    {
        $flat = [];
        $walk = function (array $nodo, ?int $parentId = null) use (&$walk, &$flat) {
            $flat[] = ['id' => (int) $nodo['id'], 'parent' => $parentId];
            foreach ($nodo['children'] ?? [] as $hijo) {
                $walk($hijo, (int) $nodo['id']);
            }
        };
        foreach ($raices as $raiz) {
            $walk($raiz);
        }
        return $flat;
    }

    private function respuestaIndex(?string $flash = null, string $nivel = 'success')
    {
        $roles = Role::with('permissions:id,name')
            ->where('name', '!=', 'SUPERADMIN')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]);

        $permisoRoles = [];
        foreach ($roles as $role) {
            foreach ($role['permissions'] as $perm) {
                $permisoRoles[$perm][] = $role['name'];
            }
        }

        $items = MenuItem::with('children')
            ->whereNull('parent_id')
            ->orderBy('orden')
            ->get()
            ->map(fn (MenuItem $item) => $this->mapNode($item, $permisoRoles));

        $permisos = Permission::orderBy('name')->pluck('name');

        $payload = [
            'title' => 'Menús',
            'items' => $items,
            'permisos' => $permisos,
            'roles' => $roles,
            'parents' => MenuItem::orderBy('label')->get(['id', 'label', 'parent_id']),
        ];

        if ($flash !== null) {
            return back()
                ->with('success', $flash);
        }

        return Inertia::render('MenuItems/Index', $payload);
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

        $nuevoOrden = isset($datos['orden']) ? (int) $datos['orden'] : null;
        $parentId = $datos['parent_id'] ?? $menuItem->parent_id;

        if ($nuevoOrden !== null && $nuevoOrden !== $menuItem->orden) {
            MenuItem::where('parent_id', $parentId)
                ->where('id', '!=', $menuItem->id)
                ->where('orden', '>=', $nuevoOrden)
                ->increment('orden');
        }

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
        Log::info('toggleVisibility called', [
            'menuItemId' => $menuItem->id,
            'label' => $menuItem->label,
            'permission' => $menuItem->permission,
            'roleId' => $role->id,
            'roleName' => $role->name,
        ]);

        $this->authorize('update', $menuItem);

        $permiso = $menuItem->permission;
        if (! $permiso) {
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

    private function mapNode(MenuItem $item, array $permisoRoles = []): array
    {
        $hijos = $item->children
            ->where('activo', true)
            ->sortBy('orden')
            ->values()
            ->map(fn (MenuItem $hijo) => $this->mapNode($hijo, $permisoRoles));

        return [
            'id' => $item->id,
            'label' => $item->label,
            'icon' => $item->icon,
            'route' => $item->route,
            'permission' => $item->permission,
            'roles' => $item->permission ? ($permisoRoles[$item->permission] ?? []) : [],
            'orden' => $item->orden,
            'activo' => $item->activo,
            'parent_id' => $item->parent_id,
            'children' => $hijos,
        ];
    }
}
