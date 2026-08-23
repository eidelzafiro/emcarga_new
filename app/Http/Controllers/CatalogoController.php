<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Http\Requests\CatalogoItemRequest;
use App\Models\CatalogoItem;
use App\Models\CatalogoTipo;
use App\Support\CatalogoSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    use EntidadScoping;

    /** Tipos de catálogo cuyo alcance es por-entidad (id_entidad en el JSON extra). */
    protected function esCatalogoPorEntidad(string $tipo): bool
    {
        return in_array($tipo, ['tipos_modelo']);
    }

    /** Autoriza si el ítem pertenece a una entidad permitida (catálogos por-entidad). */
    protected function autorizarItemCatalogo(CatalogoItem $item, string $tipo): void
    {
        if (! $this->esCatalogoPorEntidad($tipo)) {
            return;
        }

        $entidad = (int) ($item->extra['id_entidad'] ?? 0);
        $this->autorizarEntidad($entidad ?: null, 'No tiene permiso para acceder a este elemento del catálogo.');
    }

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
            'fields' => 'sometimes|array',
            'fields.*.label' => 'nullable|string|max:255',
            'fields.*.type' => 'nullable|string|max:20',
            'fields.*.required' => 'sometimes|boolean',
            'fields.*.options' => 'nullable|array',
        ]);

        // Si viene `fields` se persiste como JSON (fuente de verdad del
        // formulario). Vacío/ausente -> se deja como está.
        if (array_key_exists('fields', $data)) {
            $fields = $data['fields'];
            $data['fields'] = $fields !== []
                ? json_encode($fields, JSON_UNESCAPED_UNICODE)
                : null;
        }

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
        // (no hay columna dedicada). Se filtran por las entidades permitidas.
        if ($this->esCatalogoPorEntidad($tipo)) {
            $entidades = $this->entidadesPermitidas();
            if (! empty($entidades)) {
                $query->where(function ($q) use ($entidades) {
                    foreach ($entidades as $e) {
                        $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.id_entidad')) = ?", [(string) $e]);
                    }
                });
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

                // La ruta relativa del disco público se convierte en URL servible (/storage/...)
                if (! empty($row['imagen']) && is_string($row['imagen'])) {
                    $row['imagen'] = Storage::disk('public')->url($row['imagen']);
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

        // Imagen ilustrativa subida por el usuario (tipos de equipos).
        // Valida y guarda en el disco público; la ruta va al JSON extra.
        if ($tipo === 'tipos_equipos') {
            $ruta = $this->guardarImagenEquipo($request);
            if ($ruta) {
                $extra = $itemData['extra'] ?? [];
                $extra['imagen'] = $ruta;
                unset($extra['imagen_fuente']);
                $itemData['extra'] = $extra ?: null;
            }
        }

        if (in_array($tipo, ['tipos_modelo'])) {
            $entidadId = (int) entidadActivaId();
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

        $this->autorizarItemCatalogo($item, $tipo);

        $itemData = $request->itemData();

        // Imagen nueva: elimina la anterior del disco y actualiza extra + legacy.
        if ($tipo === 'tipos_equipos' && ($ruta = $this->guardarImagenEquipo($request))) {
            $anterior = $item->extra['imagen'] ?? null;
            if ($anterior) {
                Storage::disk('public')->delete($anterior);
            }

            $extra = $item->extra ?? [];
            unset($itemData['extra']['imagen_fuente']);
            $extra = array_merge($extra, $itemData['extra'] ?? [], ['imagen' => $ruta]);
            $itemData['extra'] = $extra;
        }

        if ($this->esCatalogoPorEntidad($tipo)) {
            $extra = $item->extra ?? [];
            if (! isset($extra['id_entidad'])) {
                $extra['id_entidad'] = (int) entidadActivaId();
            }
            $itemData['extra'] = $itemData['extra'] ?? [];
            $itemData['extra']['id_entidad'] = $extra['id_entidad'];
        }

        $item->update($itemData);

        // Paridad con la tabla legacy (fuente de los re-syncs del ETL):
        // si el ítem proviene de tipos_equipos, se replica la imagen ahí.
        if ($tipo === 'tipos_equipos'
            && isset($itemData['extra']['imagen'])
            && $item->origen_id
            && Schema::hasTable('tipos_equipos')
            && DB::table('tipos_equipos')->where('id', $item->origen_id)->exists()) {
            DB::table('tipos_equipos')->where('id', $item->origen_id)->update([
                'imagen' => $itemData['extra']['imagen'],
                'imagen_fuente' => null,
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    /**
     * Valida y guarda la imagen subida para un tipo de equipo.
     * Devuelve la ruta relativa en el disco público o null si no vino archivo.
     */
    private function guardarImagenEquipo(Request $request): ?string
    {
        if (! $request->hasFile('imagen_archivo')) {
            return null;
        }

        $request->validate([
            'imagen_archivo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=1024,max_height=1024'],
        ], [
            'imagen_archivo.image' => 'El archivo debe ser una imagen.',
            'imagen_archivo.mimes' => 'Formatos permitidos: JPG, PNG o WEBP.',
            'imagen_archivo.max' => 'La imagen no puede superar 2 MB.',
            'imagen_archivo.dimensions' => 'La imagen no puede superar 1024x1024 píxeles.',
        ]);

        return $request->file('imagen_archivo')->store('tipos_equipos', 'public');
    }

    public function destroy(string $tipo, $id)
    {
        $item = CatalogoItem::tipo($tipo)->findOrFail($id);

        $this->autorizarItemCatalogo($item, $tipo);

        $item->delete();

        return redirect()->back()->with('success', 'Eliminado correctamente');
    }
}
