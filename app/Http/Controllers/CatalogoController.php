<?php

namespace App\Http\Controllers;

use App\Models\CatalogoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        'tipos_catalogo_lugares' => ['abreviatura' => ['label' => 'Abreviatura', 'type' => 'text']],
        'tipos_articulos_bolsa' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_calificadores' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_color_piel' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_especialidad' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_integracion_politica' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_jefe_grupo' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_nivel_educacion' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_plantillas' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_ramas' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_sexo' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_tallas' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_ubicacion_defensa' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_clasificacion_laboral' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_indicadores' => ['descripcion' => ['label' => 'Descripción', 'type' => 'textarea']],
        'tipos_sistemas_cuc' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_subcta_unidad' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_suspension' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_medios_proteccion' => ['descripcion' => ['label' => 'Descripción', 'type' => 'text']],
        'tipos_arrastres' => [
            'descripcion' => ['label' => 'Descripción', 'type' => 'textarea'],
            'capacidad_toneladas' => ['label' => 'Capacidad (ton)', 'type' => 'number'],
        ],
        'tipos_causas_baja' => ['id_tipo_causa_laboral' => ['label' => 'Causa Laboral ID', 'type' => 'number']],
        'tipos_causas_movimiento' => ['id_tipo_causa_laboral' => ['label' => 'Causa Laboral ID', 'type' => 'number']],
        'tipos_clasificacion_laboral' => [
            'designado' => ['label' => 'Designado', 'type' => 'boolean'],
            'cuadro' => ['label' => 'Cuadro', 'type' => 'boolean'],
        ],
        'tipos_indicadores' => ['unidad' => ['label' => 'Unidad', 'type' => 'text']],
        'tipos_integracion_politica' => [
            'politica' => ['label' => 'Política', 'type' => 'text'],
            'abreviatura' => ['label' => 'Abreviatura', 'type' => 'text'],
        ],
        'tipos_nivel_educacion' => ['abreviatura' => ['label' => 'Abreviatura', 'type' => 'text']],
        'tipos_tasas' => [
            'unidad' => ['label' => 'Unidad', 'type' => 'text'],
            'valor' => ['label' => 'Valor', 'type' => 'number'],
        ],
        'tipos_aceites' => [],
        'tipos_agregados' => [],
        'tipos_cargas' => [],
        'tipos_combustibles' => [],
        'tipos_conceptos' => [],
        'tipos_contratos' => [],
        'tipos_neumaticos' => [],
        'tipos_incidencias' => [],
        'tipos_equipos' => [],
        'tipos_documentos' => [],
        'tipos_entidad' => [],
        'tipos_estado_civil' => [],
        'tipos_grupo_horario' => [],
        'tipos_lubricantes' => [],
        'tipos_medios_proteccion' => [],
        'tipos_pagos_adicionales' => [],
        'tipos_penalizaciones' => [],
        'tipos_roturas' => [],
        'tipos_servicios' => [],
        'tipos_sexo' => [],
        'tipos_sistemas' => [],
        'tipos_sistemas_pago' => [],
        'tipos_tallas' => [],
        'tipos_causas_laborales' => [],
    ];

    private static array $titles = [
        'tipos_aceites' => 'Tipos de Aceites',
        'tipos_agregados' => 'Tipos de Agregados',
        'tipos_arrastres' => 'Tipos de Arrastres',
        'tipos_articulos_bolsa' => 'Artículos de Bolsa',
        'tipos_calificadores' => 'Calificadores',
        'tipos_cargas' => 'Tipos de Cargas',
        'tipos_catalogo_lugares' => 'Catálogo de Lugares',
        'tipos_causas' => 'Causas',
        'tipos_causas_baja' => 'Causas de Baja',
        'tipos_causas_laborales' => 'Causas Laborales',
        'tipos_causas_movimiento' => 'Causas de Movimiento',
        'tipos_clasificacion_laboral' => 'Clasificación Laboral',
        'tipos_color_piel' => 'Color de Piel',
        'tipos_combustibles' => 'Tipos de Combustibles',
        'tipos_conceptos' => 'Conceptos',
        'tipos_contratos' => 'Tipos de Contratos',
        'tipos_deducciones' => 'Deducciones',
        'tipos_documentos' => 'Tipos de Documentos',
        'tipos_entidad' => 'Tipos de Entidad',
        'tipos_equipos' => 'Tipos de Equipos',
        'tipos_especialidad' => 'Especialidad',
        'tipos_estado_civil' => 'Estado Civil',
        'tipos_estados' => 'Estados',
        'tipos_gastos' => 'Tipos de Gastos',
        'tipos_grupo_horario' => 'Grupo Horario',
        'tipos_incidencias' => 'Incidencias',
        'tipos_indicadores' => 'Indicadores',
        'tipos_integracion_politica' => 'Integración Política',
        'tipos_jefe_grupo' => 'Jefe de Grupo',
        'tipos_lubricantes' => 'Tipos de Lubricantes',
        'tipos_mantenimiento' => 'Tipos de Mantenimiento',
        'tipos_medios_proteccion' => 'Medios de Protección',
        'tipos_neumaticos' => 'Tipos de Neumáticos',
        'tipos_nivel_educacion' => 'Nivel de Educación',
        'tipos_operaciones' => 'Tipos de Operaciones',
        'tipos_pagos_adicionales' => 'Pagos Adicionales',
        'tipos_penalizaciones' => 'Penalizaciones',
        'tipos_plantillas' => 'Plantillas',
        'tipos_ramas' => 'Ramas',
        'tipos_roturas' => 'Tipos de Roturas',
        'tipos_servicios' => 'Tipos de Servicios',
        'tipos_sexo' => 'Sexo',
        'tipos_sistemas' => 'Sistemas',
        'tipos_sistemas_cuc' => 'Sistemas CUC',
        'tipos_sistemas_pago' => 'Sistemas de Pago',
        'tipos_subcta_unidad' => 'Subcuenta Unidad',
        'tipos_suspension' => 'Suspensión',
        'tipos_tallas' => 'Tallas',
        'tipos_tasas' => 'Tasas',
        'tipos_ubicacion_defensa' => 'Ubicación Defensa',
        'tipos_vehiculos' => 'Tipos de Vehículos',
        'tipo_ingresos' => 'Tipos de Ingresos',
    ];

    private static array $withSoftDelete = [
        'tipos_operaciones',
        'tipos_mantenimiento',
    ];

    protected function getExtraFields(string $tipo): array
    {
        return self::$extraFields[$tipo] ?? [];
    }

    protected function getTitle(string $tipo): string
    {
        return self::$titles[$tipo] ?? $tipo;
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
        $grupos = [
            'Técnica' => ['tipos_aceites', 'tipos_agregados', 'tipos_arrastres', 'tipos_combustibles', 'tipos_equipos', 'tipos_lubricantes', 'tipos_neumaticos', 'tipos_roturas', 'tipos_tractivos_alternativo', 'tipos_vehiculos'],
            'Comercial' => ['tipos_cargas', 'tipos_catalogo_lugares', 'tipos_servicios', 'tipos_gastos', 'tipos_contratos', 'tipos_conceptos', 'tipos_entidad'],
            'RRHH' => ['tipos_calificadores', 'tipos_causas', 'tipos_causas_baja', 'tipos_causas_laborales', 'tipos_causas_movimiento', 'tipos_clasificacion_laboral', 'tipos_color_piel', 'tipos_deducciones', 'tipos_documentos', 'tipos_especialidad', 'tipos_estado_civil', 'tipos_estados', 'tipos_grupo_horario', 'tipos_incidencias', 'tipos_indicadores', 'tipos_integracion_politica', 'tipos_jefe_grupo', 'tipos_medios_proteccion', 'tipos_nivel_educacion', 'tipos_pagos_adicionales', 'tipos_penalizaciones', 'tipos_plantillas', 'tipos_ramas', 'tipos_sexo', 'tipos_sistemas_cuc', 'tipos_sistemas_pago', 'tipos_subcta_unidad', 'tipos_suspension', 'tipos_tallas', 'tipos_ubicacion_defensa', 'tipo_ingresos', 'tipos_mantenimiento', 'tipos_operaciones'],
            'Facturación' => ['tipos_tasas'],
            'Sistemas' => ['tipos_sistemas', 'tipos_articulos_bolsa'],
        ];

        $gruposConTitulos = [];
        foreach ($grupos as $grupo => $tipos) {
            $gruposConTitulos[$grupo] = array_map(fn($t) => [
                'tipo' => $t,
                'titulo' => self::$titles[$t] ?? $t,
            ], $tipos);
        }

        return Inertia::render('Catalogo/Tipos', [
            'grupos' => $gruposConTitulos,
            'catalogConfig' => [
                'route' => 'catalogo',
            ],
        ]);
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
