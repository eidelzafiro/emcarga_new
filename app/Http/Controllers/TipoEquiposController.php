<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\TipoEquipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TipoEquiposController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return TipoEquipo::class;
    }

    protected function getRouteName(): string
    {
        return 'tipos-equipos';
    }

    protected function getTitle(): string
    {
        return 'Tipos de Equipos';
    }

    protected function getSortField(): string
    {
        return 'nombre';
    }

    protected function getSearchFields(): array
    {
        return ['nombre'];
    }

    public function index(Request $request)
    {
        $query = TipoEquipo::query();

        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                foreach ($this->getSearchFields() as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        $items = $query->orderBy($this->getSortField())->paginate(20);

        $items->getCollection()->transform(function ($item) {
            if (! empty($item->imagen)) {
                $item->imagen = Storage::disk('public')->url($item->imagen);
            }

            return $item;
        });

        return Inertia::render('Catalogo/Index', [
            'title' => $this->getTitle(),
            'items' => $items,
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
        $data = $request->validate($this->getValidationRules());

        if ($ruta = $this->guardarImagen($request)) {
            $data['imagen'] = $ruta;
        }

        TipoEquipo::create($data);

        if ($request->boolean('_continuar')) {
            return redirect()->back()->with('success', 'Creado correctamente. Puede continuar añadiendo.');
        }

        return redirect()->back()->with('success', 'Creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $item = TipoEquipo::findOrFail($id);
        $data = $request->validate($this->getValidationRules($id));

        if ($ruta = $this->guardarImagen($request)) {
            if ($item->imagen) {
                Storage::disk('public')->delete($item->imagen);
            }
            $data['imagen'] = $ruta;
        }

        $item->update($data);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    protected function getExtraFields(): array
    {
        return [
            'imagen' => ['label' => 'Imagen ilustrativa', 'type' => 'imagen', 'required' => false, 'grid' => false],
            'activo' => ['label' => 'Activo', 'type' => 'boolean', 'required' => false],
        ];
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
            'imagen_archivo' => [
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:max_width=1024,max_height=1024',
            ],
        ];
    }

    private function guardarImagen(Request $request): ?string
    {
        if (! $request->hasFile('imagen_archivo')) {
            return null;
        }

        return $request->file('imagen_archivo')->store('tipos_equipos', 'public');
    }
}
