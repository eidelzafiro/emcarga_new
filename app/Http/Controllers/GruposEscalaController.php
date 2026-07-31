<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\GrupoEscala;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GruposEscalaController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return GrupoEscala::class;
    }

    protected function getRouteName(): string
    {
        return 'grupos-escala';
    }

    protected function getTitle(): string
    {
        return 'Grupos Escala';
    }

    protected function getExtraFields(): array
    {
        return [
            'tarifa' => ['label' => 'Tarifa', 'type' => 'number'],
            'salario' => ['label' => 'Salario', 'type' => 'number'],
        ];
    }

    public function index(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');

        $entidadId = (int) session('entidad_activa_id');
        $query = GrupoEscala::query();
        $query->where(function ($q) use ($entidadId) {
            $q->where('id_entidad', $entidadId)->orWhereNull('id_entidad');
        });
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
                'codigoManual' => $this->usaCodigoManual(),
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
        $model = $this->getModelClass();
        $data = $request->validate($this->getValidationRules());

        if (! $this->usaCodigoManual() && Schema::hasColumn((new $model)->getTable(), 'codigo')) {
            $data['codigo'] = $this->generarCodigo();
        }

        $data['id_entidad'] = (int) session('entidad_activa_id');

        $model::create($data);

        if ($request->boolean('_continuar')) {
            return redirect()->back()->with('success', 'Creado correctamente. Puede continuar añadiendo.');
        }

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
}
