<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoModelo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TiposModeloController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoModelo::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-modelo';
    }

    protected function getTitle(): string
    {
        return 'Tipos Modelo';
    }

    protected function getSearchFields(): array
    {
        return ['nombre', 'codigo'];
    }

    protected function getSortField(): string
    {
        return 'nombre';
    }

    protected function getExtraFields(): array
    {
        return [
            'ancho' => ['label' => 'Ancho', 'type' => 'number'],
            'alto' => ['label' => 'Alto', 'type' => 'number'],
        ];
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'ancho' => 'nullable|numeric',
            'alto' => 'nullable|numeric',
            'activo' => 'boolean',
        ];
    }

    public function index(Request $request)
    {
        $model = $this->getModelClass();
        $entidadId = (int) session('entidad_activa_id');

        $query = $model::where('id_entidad', $entidadId);
        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                foreach ($this->getSearchFields() as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        return Inertia::render('Catalogo/Index', [
            'title' => $this->getTitle(),
            'items' => $query->orderBy($this->getSortField())->paginate(20),
            'filters' => $request->only('search'),
            'catalogConfig' => [
                'route' => $this->getRouteName(),
                'title' => $this->getTitle(),
                'codigoManual' => false,
                'fields' => array_merge(
                    ['nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true]],
                    $this->getExtraFields()
                ),
                'extra' => $this->getExtraFields(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $modelClass = $this->getModelClass();
        $data = $request->validate($this->getValidationRules());

        $data['id_entidad'] = (int) session('entidad_activa_id');
        $max = $modelClass::max('codigo') ?? 0;
        $data['codigo'] = $max + 1;

        $modelClass::create($data);

        return redirect()->back()->with('success', 'Creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $modelClass = $this->getModelClass();
        $item = $modelClass::findOrFail($id);
        $data = $request->validate($this->getValidationRules($id));

        $item->update($data);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }
}
