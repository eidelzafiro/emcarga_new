<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\CatalogoItem;
use App\Models\Lubricante;
use App\Models\TipoArrastre;
use App\Models\TipoCombustible;
use App\Models\TipoEquipo;
use App\Support\Catalogos;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposArrastresController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoArrastre::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-arrastres';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Arrastres';
    }

    protected function getSortField(): string
    {
        return 'id';
    }

    protected function getSearchFields(): array
    {
        return ['fabricacion'];
    }

    public function index(Request $request)
    {
        $query = TipoArrastre::with(['tipoVehiculo.marca', 'tipoVehiculo.modelo', 'tipoVehiculo.tipoEquipo', 'tipoVehiculo.tipoMantenimiento'])
            ->withCount('tractivos')
            ->select('tipos_arrastres.*')
            ->leftJoin('tipo_vehiculos as tv', 'tv.id_tipo_arrastre', '=', 'tipos_arrastres.id')
            ->leftJoin('catalogo_items as marca_ord', 'marca_ord.id', '=', 'tv.id_marca')
            ->leftJoin('catalogo_items as modelo_ord', 'modelo_ord.id', '=', 'tv.id_modelo')
            ->leftJoin('catalogo_items as pais_ord', 'pais_ord.id', '=', 'marca_ord.id_pais')
            ->orderBy('marca_ord.nombre')
            ->orderBy('modelo_ord.nombre')
            ->orderBy('pais_ord.nombre');

        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fabricacion', 'like', "%{$search}%")
                    ->orWhere('eject_trac', 'like', "%{$search}%");
            });
        }

        if ($request->filled('id_marca')) {
            $query->where('tv.id_marca', $request->get('id_marca'));
        }
        if ($request->filled('id_modelo')) {
            $query->where('tv.id_modelo', $request->get('id_modelo'));
        }
        if ($request->filled('id_tipo_equipo')) {
            $query->where('tv.id_tipo_equipo', $request->get('id_tipo_equipo'));
        }
        if ($request->filled('id_pais')) {
            $query->where('marca_ord.id_pais', $request->get('id_pais'));
        }

        if ($request->filled('cantidad_vehiculos')) {
            $v = $request->get('cantidad_vehiculos');
            if ($v === '0') {
                $query->whereDoesntHave('tractivos');
            } else {
                $query->whereHas('tractivos');
            }
        }

        $items = $query->orderBy($this->getSortField())->paginate(20);

        $items->getCollection()->transform(function ($item) {
            $partes = array_filter([
                $item->tipoVehiculo?->marca?->nombre,
                $item->tipoVehiculo?->modelo?->nombre,
                $item->tipoVehiculo?->tipoEquipo?->nombre,
            ]);
            $item->nombre = $partes ? implode(' - ', $partes) : ('Arrastre '.$item->id);
            $item->cantidad_vehiculos = $item->cantidad_vehiculos;
            $item->id_pais = $item->tipoVehiculo?->marca?->id_pais ?? null;

            return $item;
        });

        return Inertia::render('Catalogo/Index', [
            'title' => $this->getTitle(),
            'items' => $items,
            'filters' => $request->only(['search', 'id_marca', 'id_modelo', 'id_tipo_equipo', 'id_pais', 'cantidad_vehiculos']),
            'catalogConfig' => [
                'route' => $this->getRouteName(),
                'title' => $this->getTitle(),
                'codigoManual' => false,
                'hideNombre' => true,
                'fields' => [
                    'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
                    'cantidad_vehiculos' => ['label' => 'Vehículos', 'type' => 'text', 'noForm' => true],
                ],
                'gridOnly' => ['id_pais', 'cantidad_vehiculos'],
                'filters' => [
                    'id_marca' => $this->filtroCatalogo('id_marca', 'marcas'),
                    'id_modelo' => $this->filtroCatalogo('id_modelo', 'modelos'),
                    'id_pais' => $this->filtroCatalogo('id_pais', 'paises'),
                    'id_tipo_equipo' => $this->filtroTipoEquipo(),
                    'cantidad_vehiculos' => [
                        'key' => 'cantidad_vehiculos',
                        'label' => 'Vehículos',
                        'options' => [
                            ['label' => 'Todos', 'value' => ''],
                            ['label' => 'Con vehículos', 'value' => '1'],
                            ['label' => 'Sin vehículos', 'value' => '0'],
                        ],
                    ],
                ],
                'extra' => $this->getExtraFields(),
            ],
        ]);
    }

    protected function getExtraFields(): array
    {
        return [
            'id_pais' => $this->select('País', 'paises'),
            'fabricacion' => $this->num('Año fabricación'),
            'frecuencia' => $this->num('Frecuencia'),
            'id_medida_del' => $this->select('Medida neum. delantero', 'medidas_neumaticos'),
            'id_medida_tra' => $this->select('Medida neum. trasero', 'medidas_neumaticos'),
            'id_medida_res' => $this->select('Medida neum. respaldo', 'medidas_neumaticos'),
            'neum_del_cant' => $this->num('Neum. delanteros (cant)'),
            'neum_tras_cant' => $this->num('Neum. traseros (cant)'),
            'neum_resp_cant' => $this->num('Neum. respaldo (cant)'),
            'id_tipo_suspension' => $this->select('Tipo de suspensión', 'tipos_suspension'),
            'ejes_cant' => $this->num('Ejes (cant)'),
            'eject_trac' => $this->texto('Tipo de tracción'),
            'dist_frente' => $this->num('Dist. frente'),
            'dist_trasera' => $this->num('Dist. trasera'),
            'largo_garganta' => $this->num('Largo garganta'),
            'altura_piso' => $this->num('Altura piso'),
            'altura_total' => $this->num('Altura total'),
            'largo_total' => $this->num('Largo total'),
            'ancho_total' => $this->num('Ancho total'),
            'id_tipo_combustible' => $this->select('Tipo de combustible', TipoCombustible::class),
            'id_lubricante' => $this->select('Lubricante', Lubricante::class),
            'id_lub_cubo' => $this->select('Lubricante cubo', Lubricante::class),
            'activo' => $this->booleano('Activo'),
        ];
    }

    protected function getValidationRules($id = null): array
    {
        $rules = ['activo' => 'boolean'];

        foreach ($this->getExtraFields() as $key => $cfg) {
            if (($cfg['type'] ?? 'text') === 'number') {
                $rules[$key] = 'nullable|numeric';
            } elseif (($cfg['type'] ?? 'text') === 'boolean') {
                $rules[$key] = 'boolean';
            } else {
                $rules[$key] = 'nullable|string|max:255';
            }
        }

        return $rules;
    }

    private function base(string $etiqueta, string $tipo): array
    {
        return ['label' => $etiqueta, 'type' => $tipo, 'required' => false, 'grid' => false];
    }

    private function texto(string $etiqueta): array
    {
        return $this->base($etiqueta, 'text');
    }

    private function num(string $etiqueta): array
    {
        return $this->base($etiqueta, 'number');
    }

    private function booleano(string $etiqueta): array
    {
        return $this->base($etiqueta, 'boolean');
    }

    private function select(string $etiqueta, string $tipo): array
    {
        if (class_exists($tipo) && is_a($tipo, \Illuminate\Database\Eloquent\Model::class, true)) {
            $options = $tipo::where('activo', true)->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn ($f) => ['value' => (int) $f->id, 'label' => (string) $f->nombre])->toArray();
        } else {
            $options = collect(Catalogos::opciones($tipo))
                ->map(fn ($o) => ['value' => $o['id'], 'label' => (string) $o['nombre']])->toArray();
        }

        return array_merge($this->base($etiqueta, 'select'), ['options' => $options]);
    }

    private function selectModelo(string $etiqueta, string $modelo): array
    {
        $options = $modelo::where('activo', true)->orderBy('nombre')
            ->get()->map(fn ($f) => ['value' => (int) $f->id, 'label' => (string) $f->nombre])->toArray();

        return array_merge($this->base($etiqueta, 'select'), ['options' => $options]);
    }

    private function filtroCatalogo(string $key, string $tipo): array
    {
        $options = collect(Catalogos::opciones($tipo))
            ->map(fn ($o) => ['value' => $o['id'], 'label' => (string) $o['nombre']])->toArray();

        return ['key' => $key, 'label' => $key, 'options' => $options];
    }

    private function filtroTipoEquipo(): array
    {
        $valores = TipoEquipo::where('activo', true)->orderBy('nombre')
            ->get()->map(fn ($v) => ['value' => (int) $v->id, 'label' => (string) $v->nombre])
            ->toArray();

        return ['key' => 'id_tipo_equipo', 'label' => 'Tipo de equipo', 'options' => $valores];
    }
}
