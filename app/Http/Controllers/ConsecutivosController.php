<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Consecutivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConsecutivosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Consecutivo::class;
    }

    protected function getRouteName(): string
    {
        return 'consecutivos';
    }

    protected function getTitle(): string
    {
        return 'Consecutivos';
    }

    protected function getSortField(): string
    {
        return 'codigo';
    }

    protected function getSearchFields(): array
    {
        return ['codigo', 'descripcion'];
    }

    protected function getExtraFields(): array
    {
        return [
            'ultimo' => ['label' => 'Último Valor', 'type' => 'number'],
        ];
    }

    protected function usaCodigoManual(): bool
    {
        return true;
    }

    public function index(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');

        $query = Consecutivo::where('id_entidad', $entidadId);
        $search = $request->get('search');

        if ($search) {
            $query->where(function ($q) use ($search) {
                foreach ($this->getSearchFields() as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        $items = $query->orderBy($this->getSortField())->paginate(20);

        $items->getCollection()->transform(fn ($item) => [
            'id' => $item->id,
            'nombre' => $item->descripcion,
            'valor' => $item->ultimo,
            'activo' => $item->activo,
            'id_entidad' => $item->id_entidad,
        ]);

        return Inertia::render('Catalogo/Index', [
            'title' => $this->getTitle(),
            'items' => $items,
            'filters' => $request->only('search'),
            'catalogConfig' => [
                'route' => $this->getRouteName(),
                'title' => $this->getTitle(),
                'codigoManual' => false,
                'fields' => [
                    'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
                ],
                'extra' => [
                    'valor' => ['label' => 'Valor', 'type' => 'number'],
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->getValidationRules());

        $data['id_entidad'] = (int) session('entidad_activa_id');
        $data['descripcion'] = $data['nombre'];
        $data['ultimo'] = $data['valor'] ?? 0;
        if (empty($data['codigo'])) {
            $data['codigo'] = $this->generarCodigo();
        }
        unset($data['nombre'], $data['valor']);

        Consecutivo::create($data);

        return redirect()->back()->with('success', 'Creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $item = Consecutivo::findOrFail($id);
        $data = $request->validate($this->getValidationRules($id));

        $data['descripcion'] = $data['nombre'];
        $data['ultimo'] = $data['valor'] ?? 0;
        unset($data['nombre'], $data['valor']);

        $item->update($data);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    public function destroy($id)
    {
        $item = Consecutivo::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Eliminado correctamente');
    }

    protected function getValidationRules($id = null): array
    {
        $entidadId = (int) session('entidad_activa_id');
        $table = 'consecutivos';

        $codigoUnique = $id
            ? "unique:{$table},codigo,{$id},id,id_entidad,{$entidadId}"
            : "unique:{$table},codigo,NULL,id,id_entidad,{$entidadId}";

        $requiredCodigo = $id ? 'nullable' : 'nullable';

        return [
            'codigo' => "nullable|string|max:50|{$codigoUnique}",
            'nombre' => 'required|string|max:255',
            'valor' => 'nullable|integer|min:0',
            'activo' => 'boolean',
        ];
    }
}
