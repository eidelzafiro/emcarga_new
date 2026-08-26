<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Lubricante;
use App\Models\TipoCombustible;
use App\Models\TipoEquipo;
use App\Models\TipoTractivo;
use App\Support\Catalogos;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposTractivosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoTractivo::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-tractivos';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Tractivos';
    }

    protected function getSortField(): string
    {
        return 'id';
    }

    public function index(Request $request)
    {
        $query = TipoTractivo::with(['tipoVehiculo.marca', 'tipoVehiculo.modelo', 'tipoVehiculo.tipoEquipo', 'tipoVehiculo.tipoMantenimiento', 'tipoCombustible'])
            ->withCount('tractivos')
            ->select('tipos_tractivos.*')
            ->leftJoin('tipo_vehiculos as tv', 'tv.id_tipo_tractivo', '=', 'tipos_tractivos.id')
            ->leftJoin('tipos_equipos as te', 'te.id', '=', 'tv.id_tipo_equipo')
            ->leftJoin('catalogo_items as marca_ord', 'marca_ord.id', '=', 'tv.id_marca')
            ->leftJoin('catalogo_items as modelo_ord', 'modelo_ord.id', '=', 'tv.id_modelo')
            ->leftJoin('catalogo_items as pais_ord', 'pais_ord.id', '=', 'marca_ord.id_pais')
            ->orderBy('marca_ord.nombre')
            ->orderBy('modelo_ord.nombre')
            ->orderBy('pais_ord.nombre');

        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('te.nombre', 'like', "%{$search}%")
                    ->orWhere('tv.fabricacion', 'like', "%{$search}%");
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
            ]);
            $item->nombre = $partes ? implode(' - ', $partes) : ('Tipo '.$item->id);
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

    public function getExtraFields(): array
    {
        return [
            'id_pais' => $this->select('País', 'paises'),
            'bat_cant' => $this->num('Baterías (cant)'),
            'bat_amp' => $this->num('Baterías (Amp)'),
            'dif_cant' => $this->num('Diferenciales (cant)'),
            'dif_relacion' => $this->texto('Relación diferencial'),
            'dif_ancho' => $this->num('Ancho diferencial'),
            'id_medida_del' => $this->select('Medida neum. delantero', 'medidas_neumaticos'),
            'id_medida_tra' => $this->select('Medida neum. trasero', 'medidas_neumaticos'),
            'id_medida_res' => $this->select('Medida neum. respaldo', 'medidas_neumaticos'),
            'neum_del_cant' => $this->num('Neum. delanteros (cant)'),
            'neum_tras_cant' => $this->num('Neum. traseros (cant)'),
            'neum_resp_cant' => $this->num('Neum. respaldo (cant)'),
            'neum_tractivos' => $this->texto('Neum. tractivos'),
            'ejes_cant' => $this->num('Ejes (cant)'),
            'eject_trac' => $this->texto('Tipo de tracción'),
            'id_tipo_combustible' => $this->select('Tipo de combustible', TipoCombustible::class),
            'id_lubricante_motor' => $this->select('Lubricante motor', Lubricante::class),
            'id_lubricante_cubo' => $this->select('Lubricante cubo', Lubricante::class),
            'lub_norma' => $this->texto('Norma lubricante'),
            'lub_caja' => $this->texto('Lubricante caja'),
            'dist_eje_inter' => $this->num('Dist. eje intermedio'),
            'dist_eje_tras' => $this->num('Dist. eje trasero'),
            'cama_largo' => $this->num('Cama largo'),
            'cama_ancho' => $this->num('Cama ancho'),
            'cama_altura' => $this->num('Cama altura'),
        ];
    }

    protected function getValidationRules($id = null): array
    {
        $rules = ['activo' => 'boolean'];

        foreach ($this->getExtraFields() as $key => $cfg) {
            $rules[$key] = $cfg['type'] === 'number'
                ? 'nullable|numeric'
                : 'nullable|string|max:255';
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
