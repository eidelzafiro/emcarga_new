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

    protected function getNombreConfig(): array
    {
        return [];
    }

    protected function isEntityScoped(): bool
    {
        return false;
    }

    protected function applyEntityScope($query, int $entidadId): void
    {
        if ($entidadId > 0) {
            $query->where('id_entidad', $entidadId);
        }
    }

    protected function getScopingData(): array
    {
        return ['id_entidad' => (int) session('entidad_activa_id')];
    }

    protected function getSortField(): string
    {
        return 'nombre';
    }

    protected function getSearchFields(): array
    {
        return ['codigo', 'nombre'];
    }

    protected function usaCodigoManual(): bool
    {
        return false;
    }

    protected function getValidationRules($id = null): array
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
        ];

        foreach ($this->getExtraFields() as $key => $config) {
            $type = $config['type'] ?? 'text';
            $required = $config['required'] ?? false;
            $base = $required ? 'required' : 'nullable';

            if ($type === 'boolean') {
                $rules[$key] = 'boolean';
            } elseif ($type === 'number') {
                $rules[$key] = $base.'|numeric';
            } elseif ($type === 'select') {
                $rules[$key] = $base;
            } else {
                $rules[$key] = $base.'|string';
            }
        }

        return $rules;
    }

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

        if ($this->isEntityScoped()) {
            $entidadId = (int) session('entidad_activa_id');
            if ($entidadId) {
                $this->applyEntityScope($query, $entidadId);
            }
        }

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
                    ['nombre' => array_merge(
                        ['label' => 'Nombre', 'type' => 'text', 'required' => true],
                        $this->getNombreConfig()
                    )],
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

        if ($this->isEntityScoped()) {
            $data = array_merge($data, $this->getScopingData());
        }

        if (! $this->usaCodigoManual() && Schema::hasColumn((new $model)->getTable(), 'codigo')) {
            $data['codigo'] = $this->generarCodigo();
        }

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

    public function destroy($id)
    {
        $model = $this->getModelClass();
        $item = $model::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Eliminado correctamente');
    }
}
