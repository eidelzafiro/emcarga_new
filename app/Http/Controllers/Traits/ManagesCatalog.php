<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    /**
     * Si el catálogo maneja el código manualmente (p.ej. Consecutivos,
     * donde el código ES el dato de negocio), se mantiene como input
     * del usuario. Por defecto el código es automático y correlativo.
     */
    protected function usaCodigoManual(): bool
    {
        return false;
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
        ];
    }

    /**
     * Código correlativo automático por tabla:
     * max valor numérico existente + 1, con padding de 2 dígitos.
     * La unique de la columna protege ante creaciones concurrentes.
     */
    protected function generarCodigo(): string
    {
        $model = $this->getModelClass();
        $table = (new $model)->getTable();

        $max = DB::table($table)
            ->selectRaw('MAX(CAST(codigo AS UNSIGNED)) as max_cod')
            ->value('max_cod');

        return str_pad((string) ((int) $max + 1), 2, '0', STR_PAD_LEFT);
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
