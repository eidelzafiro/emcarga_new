<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Organismo;
use App\Models\Osde;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OsdesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Osde::class;
    }

    protected function getRouteName(): string
    {
        return 'osdes';
    }

    protected function getTitle(): string
    {
        return 'OSDES';
    }

    protected function getSearchFields(): array
    {
        return ['codigo', 'nombre', 'siglas'];
    }

    protected function getExtraFields(): array
    {
        return [
            'siglas' => ['label' => 'Siglas', 'type' => 'text', 'required' => false],
        ];
    }

    private function getOrganismoOptions(): array
    {
        return Organismo::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'abreviatura'])
            ->map(fn ($o) => ['label' => $o->nombre . ($o->abreviatura ? " ({$o->abreviatura})" : ''), 'value' => $o->id])
            ->toArray();
    }

    public function index(Request $request)
    {
        $model = $this->getModelClass();

        $query = $model::with('organismo');
        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                foreach ($this->getSearchFields() as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
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
                'codigoManual' => $this->usaCodigoManual(),
                'fields' => [
                    'nombre' => ['label' => 'Nombre', 'type' => 'text', 'required' => true],
                    'siglas' => ['label' => 'Siglas', 'type' => 'text', 'required' => false],
                    'id_organismo' => [
                        'label' => 'Organismo',
                        'type' => 'select',
                        'required' => false,
                        'options' => $this->getOrganismoOptions(),
                    ],
                ],
                'extra' => $this->getExtraFields(),
            ],
        ]);
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'siglas' => 'nullable|string|max:50',
            'id_organismo' => 'nullable|exists:organismos,id',
            'activo' => 'boolean',
        ];
    }
}
