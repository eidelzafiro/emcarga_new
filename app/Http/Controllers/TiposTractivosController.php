<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Lubricante;
use App\Models\Marca;
use App\Models\MedidaNeumatico;
use App\Models\Modelo;
use App\Models\Pais;
use App\Models\TipoCombustible;
use App\Models\TipoTractivo;
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

    public function index(Request $request)
    {
        $query = TipoTractivo::with(['marca', 'modelo', 'pais', 'tipoCombustible']);

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
                'fields' => ['nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true]],
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
            'fabricacion' => $this->num('Año fabricación'),
            'tipo_equipo' => $this->texto('Tipo de equipo'),
            'bat_cant' => $this->num('Baterías (cant)'),
            'bat_amp' => $this->num('Baterías (Amp)'),
            'dif_cant' => $this->num('Diferenciales (cant)'),
            'dif_relacion' => $this->texto('Relación diferencial'),
            'dif_ancho' => $this->num('Ancho diferencial'),
            'id_medida_del' => $this->select('Medida neum. delantero', MedidaNeumatico::class),
            'id_medida_tra' => $this->select('Medida neum. trasero', MedidaNeumatico::class),
            'id_medida_res' => $this->select('Medida neum. respaldo', MedidaNeumatico::class),
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
        $rules = ['nombre' => 'required|string|max:255', 'activo' => 'boolean'];

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
