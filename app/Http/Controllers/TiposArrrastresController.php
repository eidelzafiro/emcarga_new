<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Lubricante;
use App\Models\Marca;
use App\Models\MedidaNeumatico;
use App\Models\Modelo;
use App\Models\Pais;
use App\Models\TipoArrastre;
use App\Models\TipoCombustible;
use App\Models\TipoEquipo;
use App\Models\TiposMantenimiento;
use App\Models\TipoSuspension;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposArrrastresController extends Controller
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

    public function index(Request $request)
    {
        $query = TipoArrastre::with(['marca', 'modelo', 'pais', 'tipoEquipo', 'tipoSuspension', 'tipoCombustible']);

        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy($this->getSortField())->paginate(20);

        return Inertia::render('Catalogo/Index', [
            'title' => $this->getTitle(),
            'items' => $items,
            'filters' => $request->only('search'),
            'catalogConfig' => [
                'route' => $this->getRouteName(),
                'title' => $this->getTitle(),
                'codigoManual' => false,
                'fields' => [
                    'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true, 'grid' => true],
                    'descripcion' => ['label' => 'Descripción', 'type' => 'textarea', 'required' => false, 'grid' => false],
                    'capacidad_toneladas' => ['label' => 'Capacidad (ton)', 'type' => 'number', 'required' => false, 'grid' => true],
                ],
                'extra' => $this->getExtraFields(),
            ],
        ]);
    }

    protected function getExtraFields(): array
    {
        return [
            'id_marca' => $this->select('Marca', Marca::class),
            'id_modelo' => $this->select('Modelo', Modelo::class),
            'id_pais' => $this->select('País', Pais::class),
            'id_tipo_equipo' => $this->select('Tipo de equipo', TipoEquipo::class),
            'fabricacion' => $this->num('Año fabricación'),
            'frecuencia' => $this->num('Frecuencia'),
            'id_medida_del' => $this->select('Medida neum. delantero', MedidaNeumatico::class),
            'id_medida_tra' => $this->select('Medida neum. trasero', MedidaNeumatico::class),
            'id_medida_res' => $this->select('Medida neum. respaldo', MedidaNeumatico::class),
            'neum_del_cant' => $this->num('Neum. delanteros (cant)'),
            'neum_tras_cant' => $this->num('Neum. traseros (cant)'),
            'neum_resp_cant' => $this->num('Neum. respaldo (cant)'),
            'id_tipo_suspension' => $this->select('Tipo de suspensión', TipoSuspension::class),
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
            'id_tipo_mantenimiento' => $this->select('Tipo de mantenimiento', TiposMantenimiento::class),
        ];
    }

    protected function getValidationRules($id = null): array
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'capacidad_toneladas' => 'nullable|numeric',
            'activo' => 'boolean',
        ];

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

    private function select(string $etiqueta, string $model): array
    {
        $options = $model::where('activo', true)->orderBy('nombre')
            ->get()->map(fn ($f) => ['value' => $f->id, 'label' => (string) $f->nombre])->toArray();

        return array_merge($this->base($etiqueta, 'select'), ['options' => $options]);
    }
}
