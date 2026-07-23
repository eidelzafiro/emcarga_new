<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Inertia\Inertia;

trait ManagesCatalog
{
    abstract protected function getModelClass(): string;

    abstract protected function getRouteName(): string;

    abstract protected function getTitle(): string;

    protected function getExtraFields(): array
    {
        return [];
    }

    protected function getSortField(): string
    {
        return 'nombre';
    }

    protected function getSearchFields(): array
    {
        return ['codigo', 'nombre'];
    }

    protected function getValidationRules($id = null): array
    {
        $model = $this->getModelClass();
        $table = (new $model)->getTable();
        $unique = $id ? "unique:{$table},codigo,{$id}" : "unique:{$table},codigo";

        return [
            'codigo' => "required|string|max:50|{$unique}",
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
        ];
    }

    public function index(Request $request)
    {
        $model = $this->getModelClass();

        $query = $model::query();
        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                foreach ($this->getSearchFields() as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        return Inertia::render('Catalogo/Index', [
            'items' => $query->orderBy($this->getSortField())->paginate(20),
            'filters' => $request->only('search'),
            'catalogConfig' => [
                'route' => $this->getRouteName(),
                'title' => $this->getTitle(),
                'fields' => array_merge(
                    ['codigo' => ['label' => 'Código', 'type' => 'text', 'required' => true]],
                    ['nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true]],
                    $this->getExtraFields()
                ),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $model = $this->getModelClass();
        $data = $request->validate($this->getValidationRules());

        $model::create($data);

        return redirect()->back()->with('success', 'Creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $model = $this->getModelClass();
        $item = $model::findOrFail($id);
        $data = $request->validate($this->getValidationRules($id));

        $item->update($data);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    public function destroy($id)
    {
        $model = $this->getModelClass();
        $item = $model::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Eliminado correctamente');
    }
}
