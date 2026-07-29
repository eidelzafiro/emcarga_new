<?php

namespace App\Http\Controllers;

use App\Models\CatalogoItem;
use App\Models\CatalogoTipo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    private static array $extraFields = [
        'tipos_operaciones' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_mantenimiento' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_gastos' => ['tipo' => ['label' => 'Tipo', 'type' => 'text']],
        'tipos_causas' => ['tipo' => ['label' => 'Tipo', 'type' => 'text']],
        'tipos_estados' => [
            'imagen' => ['label' => 'Imagen', 'type' => 'text'],
            'siglas' => ['label' => 'Siglas', 'type' => 'text'],
        ],
        'tipo_ingresos' => ['siglas' => ['label' => 'Siglas', 'type' => 'text']],
        'tipos_vehiculos' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_deducciones' => [
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea'],
            'clave' => ['label' => 'Clave', 'type' => 'number'],
        ],
        'tipos_color_piel' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_integracion_politica' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_nivel_educacion' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_sexo' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_ubicacion_defensa' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_indicadores' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_suspension' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_arrastres' => [
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea'],
            'capacidad_toneladas' => ['label' => 'Capacidad (ton)', 'type' => 'number'],
        ],
        'tipos_causas_baja' => ['id_tipo_causa_laboral' => ['label' => 'Causa Laboral ID', 'type' => 'number']],
        'tipos_indicadores' => ['unidad' => ['label' => 'Unidad', 'type' => 'text']],
        'tipos_integracion_politica' => [
            'politica' => ['label' => 'Política', 'type' => 'text'],
            'abreviatura' => ['label' => 'Abreviatura', 'type' => 'text'],
        ],
        'tipos_nivel_educacion' => ['abreviatura' => ['label' => 'Abreviatura', 'type' => 'text']],
        'tipos_aceites' => [],
        'tipos_agregados' => [],
        'tipos_cargas' => [],
        'tipos_combustibles' => [],
        'tipos_equipos' => [],
        'tipos_incidencias' => [],
        'tipos_neumaticos' => [],
        'tipos_documentos' => [],
        'tipos_estado_civil' => [],
        'tipos_grupo_horario' => [],
        'tipos_lubricantes' => [],
        'tipos_pagos_adicionales' => [],
        'tipos_penalizaciones' => [],
        'tipos_roturas' => [],
        'tipos_servicios' => [],
    ];

    protected function getExtraFields(string $tipo): array
    {
        return self::$extraFields[$tipo] ?? [];
    }

    protected function getTitle(string $tipo): string
    {
        return CatalogoTipo::where('tipo', $tipo)->value('titulo') ?? $tipo;
    }

    protected function usaCodigoManual(string $tipo): bool
    {
        return false;
    }

    protected function getSearchFields(string $tipo): array
    {
        return ['codigo', 'nombre'];
    }

    protected function generarCodigo(string $tipo): string
    {
        $max = CatalogoItem::where('tipo', $tipo)
            ->selectRaw('MAX(CAST(codigo AS UNSIGNED)) as max_cod')
            ->value('max_cod');

        return str_pad((string) ((int) $max + 1), 2, '0', STR_PAD_LEFT);
    }

    protected function getValidationRules(string $tipo, $id = null): array
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
        ];

        if (! $this->usaCodigoManual($tipo)) {
            $rules['codigo'] = 'nullable|string|max:50';
        } else {
            $rules['codigo'] = 'required|string|max:50';
        }

        foreach ($this->getExtraFields($tipo) as $key => $cfg) {
            $typeRules = match ($cfg['type'] ?? 'text') {
                'number' => 'nullable|numeric',
                'textarea' => 'nullable|string|max:2000',
                'boolean' => 'nullable|boolean',
                default => 'nullable|string|max:255',
            };
            $rules["extra.{$key}"] = $typeRules;
        }

        return $rules;
    }

    public function tipos()
    {
        $tipos = CatalogoTipo::where('activo', true)->orderBy('orden')->get();
        $grupos = $tipos->groupBy('agrupacion');

        $gruposConTitulos = [];
        foreach ($grupos as $agrupacion => $items) {
            $gruposConTitulos[$agrupacion] = $items->map(fn($t) => [
                'tipo' => $t->tipo,
                'titulo' => $t->titulo,
            ])->toArray();
        }

        return Inertia::render('Catalogo/Tipos', [
            'grupos' => $gruposConTitulos,
            'catalogConfig' => [
                'route' => 'catalogo',
            ],
        ]);
    }

    public function gestionar()
    {
        $this->authorize('catalogo.editar');

        $tipos = CatalogoTipo::orderBy('orden')->get()->map(fn($t) => [
            'id' => $t->id,
            'tipo' => $t->tipo,
            'titulo' => $t->titulo,
            'agrupacion' => $t->agrupacion,
            'activo' => $t->activo,
            'orden' => $t->orden,
            'items_count' => CatalogoItem::where('tipo', $t->tipo)->count(),
        ]);

        return Inertia::render('Catalogo/GestionarTipos', [
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

        if (in_array($tipo, self::$withSoftDelete)) {
            $query->withTrashed();
        }

        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                foreach ($this->getSearchFields($tipo) as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        $gridFields = $this->getExtraFields($tipo);

        return Inertia::render('Catalogo/Index', [
            'items' => $query->orderBy('nombre')->paginate(20)->through(function ($item) use ($gridFields) {
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
                'codigoManual' => $this->usaCodigoManual($tipo),
                'tipo' => $tipo,
                'fields' => array_merge(
                    ['nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true]],
                    $gridFields
                ),
                'extra' => $gridFields,
            ],
        ]);
    }

    public function store(Request $request, string $tipo)
    {
        $data = $request->validate($this->getValidationRules($tipo));

        $itemData = [
            'tipo' => $tipo,
            'nombre' => $data['nombre'],
            'activo' => $data['activo'] ?? true,
        ];

        if (isset($data['codigo'])) {
            $itemData['codigo'] = $data['codigo'];
        } elseif (! $this->usaCodigoManual($tipo)) {
            $itemData['codigo'] = $this->generarCodigo($tipo);
        }

        if (isset($data['extra'])) {
            $itemData['extra'] = array_filter($data['extra'], fn($v) => $v !== null && $v !== '');
        }

        CatalogoItem::create($itemData);

        return redirect()->back()->with('success', 'Creado correctamente');
    }

    public function update(Request $request, string $tipo, $id)
    {
        $item = CatalogoItem::tipo($tipo)->findOrFail($id);
        $data = $request->validate($this->getValidationRules($tipo, $id));

        $itemData = [
            'nombre' => $data['nombre'],
            'activo' => $data['activo'] ?? true,
        ];

        if (isset($data['codigo'])) {
            $itemData['codigo'] = $data['codigo'];
        }

        if (isset($data['extra'])) {
            $itemData['extra'] = array_filter($data['extra'], fn($v) => $v !== null && $v !== '');
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
