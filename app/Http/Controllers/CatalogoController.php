<?php

namespace App\Http\Controllers;

use App\Http\Requests\CatalogoItemRequest;
use App\Models\CatalogoItem;
use App\Models\CatalogoTipo;
use App\Support\CatalogoSchema;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    protected function getTitle(string $tipo): string
    {
        return CatalogoTipo::where('tipo', $tipo)->value('titulo') ?? $tipo;
    }

    protected function generarCodigo(string $tipo): string
    {
        $max = CatalogoItem::where('tipo', $tipo)
            ->selectRaw('MAX(CAST(codigo AS UNSIGNED)) as max_cod')
            ->value('max_cod');

        return str_pad((string) ((int) $max + 1), 2, '0', STR_PAD_LEFT);
    }

    public function tipos()
    {
        $tipos = CatalogoTipo::where('activo', true)->orderBy('orden')->get();
        $grupos = $tipos->groupBy('agrupacion');

        $gruposConTitulos = [];
        foreach ($grupos as $agrupacion => $items) {
            $gruposConTitulos[$agrupacion] = $items->map(fn ($t) => [
                'tipo' => $t->tipo,
                'titulo' => $t->titulo,
            ])->toArray();
        }

        return Inertia::render('Catalogo/Tipos', [
            'title' => 'Catálogo',
            'grupos' => $gruposConTitulos,
            'catalogConfig' => [
                'route' => 'catalogo',
            ],
        ]);
    }

    public function gestionar()
    {
        $this->authorize('catalogo.editar');

        $tipos = CatalogoTipo::orderBy('orden')->get()->map(fn ($t) => [
            'id' => $t->id,
            'tipo' => $t->tipo,
            'titulo' => $t->titulo,
            'agrupacion' => $t->agrupacion,
            'activo' => $t->activo,
            'orden' => $t->orden,
            'items_count' => CatalogoItem::where('tipo', $t->tipo)->count(),
        ]);

        return Inertia::render('Catalogo/GestionarTipos', [
            'title' => 'Gestionar Tipos',
            'tipos' => $tipos,
        ]);
    }

    public function updateTipo(Request $request, string $tipo)
    {
        $this->authorize('catalogo.editar');

        $data = $request->validate([
            'agrupacion' => 'sometimes|string|max:100',
            'activo' => 'sometimes|boolean',
        ]);

        CatalogoTipo::where('tipo', $tipo)->update($data);

        return redirect()->back()->with('success', 'Tipo actualizado correctamente.');
    }

    public function index(Request $request, string $tipo)
    {
        $query = CatalogoItem::tipo($tipo);

        if (CatalogoSchema::usaSoftDeletes($tipo)) {
            $query->withTrashed();
        }

        // Catálogos por-entidad: los ítems guardan id_entidad en el JSON `extra`
        // (no hay columna dedicada). Se filtran por la entidad activa.
        if (in_array($tipo, ['tipos_modelo'])) {
            $entidadId = (int) session('entidad_activa_id');
            if ($entidadId) {
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.id_entidad')) = ?", [$entidadId]);
            }
        }

        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search, $tipo) {
                foreach (CatalogoSchema::searchFields($tipo) as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        $gridFields = CatalogoSchema::extraFields($tipo);

        return Inertia::render('Catalogo/Index', [
            'title' => $this->getTitle($tipo),
            'items' => $query->orderBy('nombre')->paginate(20)->through(function ($item) {
                $row = $item->toArray();
                if ($item->extra && is_array($item->extra)) {
                    foreach ($item->extra as $k => $v) {
                        $row[$k] = $v;
                    }
                }

                return $row;
            }),
            'filters' => $request->only('search'),
            'catalogConfig' => [
                'route' => 'catalogo',
                'title' => $this->getTitle($tipo),
                'codigoManual' => CatalogoSchema::usaCodigoManual($tipo),
                'tipo' => $tipo,
                'fields' => array_merge(
                    ['nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true]],
                    $gridFields
                ),
                'extra' => $gridFields,
            ],
        ]);
    }

    public function store(CatalogoItemRequest $request, string $tipo)
    {
        $itemData = $request->itemData();
        $itemData['tipo'] = $tipo;

        if (in_array($tipo, ['tipos_modelo'])) {
            $entidadId = (int) session('entidad_activa_id');
            if ($entidadId) {
                $extra = $itemData['extra'] ?? [];
                $extra['id_entidad'] = $entidadId;
                $itemData['extra'] = $extra ?: null;
            }
        }

        if (! isset($itemData['codigo']) && ! CatalogoSchema::usaCodigoManual($tipo)) {
            $itemData['codigo'] = $this->generarCodigo($tipo);
        }

        CatalogoItem::create($itemData);

        return redirect()->back()->with('success', 'Creado correctamente');
    }

    public function update(CatalogoItemRequest $request, string $tipo, $id)
    {
        $item = CatalogoItem::tipo($tipo)->findOrFail($id);

        $itemData = $request->itemData();

        if (in_array($tipo, ['tipos_modelo'])) {
            $extra = $item->extra ?? [];
            if (! isset($extra['id_entidad'])) {
                $extra['id_entidad'] = (int) session('entidad_activa_id');
            }
            $itemData['extra'] = $itemData['extra'] ?? [];
            $itemData['extra']['id_entidad'] = $extra['id_entidad'];
        }

        $item->update($itemData);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    public function destroy(string $tipo, $id)
    {
        $item = CatalogoItem::tipo($tipo)->findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Eliminado correctamente');
    }
}
