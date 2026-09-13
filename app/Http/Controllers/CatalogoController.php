<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Http\Requests\CatalogoItemRequest;
use App\Models\Area;
use App\Models\CatalogoItem;
use App\Models\CatalogoTipo;
use App\Models\Entidad;
use App\Support\Catalogos;
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

        // Tipos de penalizaciones: filtrar por la entidad del área penalizada
        // (regla de negocio: se penalizan por área, el área define la entidad).
        if ($tipo === 'tipos_penalizaciones') {
            $entidades = $this->entidadesPermitidas();
            if (! empty($entidades)) {
                $idsAreas = Area::whereIn('id_entidad', $entidades)->pluck('id');
                $query->where(function ($q) use ($idsAreas) {
                    foreach ($idsAreas as $areaId) {
                        $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(extra, '$.area_id')) = ?", [(string) $areaId]);
                    }
                });
            }
        }

        // Modelos y Marcas: orden alfabético por nombre. El conteo "usos" suma
        // las referencias de TODAS las tablas de negocio (no solo tipo_vehiculos).
        $ordenarPorUsos = in_array($tipo, ['modelos', 'marcas'], true);

        if ($ordenarPorUsos) {
            $colRef = $tipo === 'modelos' ? 'id_modelo' : 'id_marca';
            $tablas = ['tipo_vehiculos', 'baterias', 'neumaticos', 'otros_agregados', 'tarjetero'];

            $subqueries = [];
            foreach ($tablas as $tabla) {
                if (Schema::hasColumn($tabla, $colRef)) {
                    $subqueries[] = "(SELECT COUNT(*) FROM {$tabla} WHERE {$tabla}.{$colRef} = catalogo_items.id)";
                }
            }
            $usosSql = $subqueries ? '('.implode(' + ', $subqueries).')' : '0';

            $query->selectRaw("catalogo_items.*, {$usosSql} as usos")
                ->orderBy('nombre');
        } else {
            $query->orderBy('nombre');
        }

        $paginator = $query->paginate(20);

        return Inertia::render('Catalogo/Index', [
            'title' => $this->getTitle($tipo),
            'items' => $paginator->through(function ($item) use ($ordenarPorUsos) {
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
                if (! empty($row['logo']) && is_string($row['logo'])) {
                    $row['logo'] = Storage::disk('public')->url($row['logo']);
                }

                if ($ordenarPorUsos) {
                    $row['usos'] = (int) ($item->usos ?? 0);
                }

                return $row;
            }),
            'filters' => $request->only('search'),
            'catalogConfig' => $this->catalogConfig($tipo, $gridFields),
        ]);
    }

    /**
     * Configuración del catálogo unificado para un tipo (grid + form).
     */
    private function catalogConfig(string $tipo, ?array $gridFields = null): array
    {
        $gridFields ??= CatalogoSchema::extraFields($tipo);

        return [
            'route' => 'catalogo',
            'title' => $this->getTitle($tipo),
            'codigoManual' => CatalogoSchema::usaCodigoManual($tipo),
            'tipo' => $tipo,
            'fields' => $this->buildCatalogFields($tipo, $gridFields),
            'extra' => $gridFields,
        ];
    }

    /**
     * Campos base del formulario/grid de un tipo de catálogo. Para las
     * marcas se añade el país como columna real (se deriva hacia los
     * tipos/agregados que la referencian).
     */
    private function buildCatalogFields(string $tipo, array $gridFields): array
    {
        $fields = array_merge(
            ['nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true]],
            $gridFields
        );

        if ($tipo === 'marcas') {
            $fields['id_pais'] = [
                'label' => 'País',
                'type' => 'select',
                'options' => collect(Catalogos::opciones('paises'))
                    ->map(fn ($o) => ['value' => $o['id'], 'label' => (string) $o['nombre']])
                    ->values()
                    ->all(),
            ];
            $fields['logo'] = [
                'label' => 'Logo',
                'type' => 'logo',
            ];
        }

        // Tipos de penalizaciones: selects con áreas de la entidad activa y
        // pagos adicionales (con el sistema de pago que penalizan en el label).
        if ($tipo === 'tipos_penalizaciones') {
            $entidadId = (int) entidadActivaId();
            $entidades = $entidadId ? Entidad::idsPermitidos($entidadId) : [];

            $fields['area_id']['options'] = Area::query()
                ->when(! empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn ($a) => ['value' => $a->id, 'label' => $a->nombre])
                ->values()
                ->all();

            $sistemas = Area::query()
                ->whereNotNull('id_tipo_sistema_pago')
                ->distinct()
                ->pluck('id_tipo_sistema_pago');

            $fields['tipo_pago_adicional_id']['options'] = CatalogoItem::query()
                ->where('tipo', 'tipos_pagos_adicionales')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'extra'])
                ->map(function ($pa) use ($sistemas) {
                    $extra = is_array($pa->extra) ? $pa->extra : (json_decode((string) $pa->extra, true) ?: []);
                    $sists = array_intersect($extra['sistemas_pago'] ?? [], $sistemas->all());
                    $label = $pa->nombre;
                    if ($sists) {
                        $nombres = CatalogoItem::query()
                            ->where('tipo', 'tipos_sistemas_pago')
                            ->whereIn('origen_id', $sists)
                            ->pluck('nombre');
                        if ($nombres->isNotEmpty()) {
                            $label .= ' ('.$nombres->implode(' / ').')';
                        }
                    }

                    return ['value' => $pa->id, 'label' => $label];
                })
                ->values()
                ->all();
        }

        return $fields;
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

        // Logo de la marca: se sube como archivo y se guarda la ruta en la
        // columna real `logo` de catalogo_items.
        if ($tipo === 'marcas' && ($ruta = $this->guardarLogoMarca($request))) {
            $itemData['logo'] = $ruta;
        }

        // Tipos de estado: la imagen ilustrativa se sube como archivo y se
        // guarda en el JSON extra (`imagen`), igual que los tipos de equipos.
        if ($tipo === 'tipos_estados' && $request->hasFile('logo_archivo')) {
            $request->validate([
                'logo_archivo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=1024,max_height=1024'],
            ]);
            $ruta = $request->file('logo_archivo')->store('tipos_estados', 'public');
            $extra = $itemData['extra'] ?? [];
            $extra['imagen'] = $ruta;
            $itemData['extra'] = $extra;
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

        // Logo nuevo de la marca: elimina el anterior y guarda la ruta.
        if ($tipo === 'marcas' && ($ruta = $this->guardarLogoMarca($request))) {
            $anterior = $item->logo;
            if ($anterior) {
                Storage::disk('public')->delete($anterior);
            }
            $itemData['logo'] = $ruta;
        }

        // Imagen de tipos de estado: se sube como archivo y se guarda en el
        // JSON extra. Se preserva la imagen previa salvo que venga una nueva,
        // y se mantienen los demás campos extra (p.ej. siglas).
        if ($tipo === 'tipos_estados') {
            $extraExistente = $item->extra ?? [];
            $imagen = $extraExistente['imagen'] ?? null;
            if ($request->hasFile('logo_archivo')) {
                $request->validate([
                    'logo_archivo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=1024,max_height=1024'],
                ]);
                if ($imagen) {
                    Storage::disk('public')->delete($imagen);
                }
                $imagen = $request->file('logo_archivo')->store('tipos_estados', 'public');
            }
            $nuevoExtra = $itemData['extra'] ?? [];
            $nuevoExtra['imagen'] = $imagen;
            $itemData['extra'] = $nuevoExtra;
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

    /**
     * Valida y guarda el logo de la marca subido por el usuario.
     * Devuelve la ruta relativa en el disco público o null si no vino archivo.
     * La regla de validación del archivo vive en CatalogoItemRequest.
     */
    private function guardarLogoMarca(Request $request): ?string
    {
        if (! $request->hasFile('logo_archivo')) {
            return null;
        }

        return $request->file('logo_archivo')->store('marcas', 'public');
    }

    public function destroy(string $tipo, $id)
    {
        $item = CatalogoItem::tipo($tipo)->findOrFail($id);

        $this->autorizarItemCatalogo($item, $tipo);

        $item->delete();

        return redirect()->back()->with('success', 'Eliminado correctamente');
    }
}
