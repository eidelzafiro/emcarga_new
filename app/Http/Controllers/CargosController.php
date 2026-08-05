<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Cargo;
use App\Models\CategoriaCargo;
use App\Models\FondoTiempo;
use App\Models\GrupoEscala;
use App\Models\TipoGrupoHorario;
use App\Models\TipoNivelEducacion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CargosController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Cargo::class;
    }

    protected function getRouteName(): string
    {
        return 'cargos';
    }

    protected function getTitle(): string
    {
        return 'Cargos';
    }

    protected function isEntityScoped(): bool
    {
        return true;
    }

    protected function getNombreConfig(): array
    {
        return ['label' => 'Nombre del Cargo', 'grid' => true];
    }

    protected function getExtraFields(): array
    {
        return [
            'id_fondo_tiempo' => [
                'label' => 'Fondo Tiempo',
                'type' => 'select',
                'required' => true,
                'grid' => false,
                'options' => $this->comboFondosTiempo(),
            ],
            'id_nivel_educacion' => [
                'label' => 'Nivel',
                'type' => 'select',
                'required' => true,
                'grid' => false,
                'options' => $this->comboNivelesEducacion(),
            ],
            'id_grupo_escala' => [
                'label' => 'Grupo Escala',
                'type' => 'select',
                'required' => true,
                'grid' => true,
                'options' => $this->comboGruposEscala(),
            ],
            'id_categoria_cargo' => [
                'label' => 'Categoría',
                'type' => 'select',
                'required' => true,
                'grid' => true,
                'options' => $this->comboCategorias(),
            ],
            'id_grupo_horario' => [
                'label' => 'Grupo Horario',
                'type' => 'select',
                'required' => true,
                'grid' => true,
                'options' => $this->comboGruposHorario(),
            ],
            'tipo_salario' => [
                'label' => 'T. Salario',
                'type' => 'select',
                'required' => true,
                'grid' => true,
                'options' => [
                    ['value' => 1, 'label' => 'Sueldo'],
                    ['value' => 0, 'label' => 'Jornal'],
                ],
            ],
            'en_salario' => [
                'label' => 'En',
                'type' => 'select',
                'required' => true,
                'grid' => true,
                'options' => [
                    ['value' => 1, 'label' => 'Días'],
                    ['value' => 0, 'label' => 'Horas'],
                ],
            ],
            'tarifa' => [
                'label' => 'Tarifa',
                'type' => 'number',
                'required' => false,
                'grid' => true,
            ],
            'cla' => [
                'label' => 'CLA',
                'type' => 'number',
                'required' => false,
                'grid' => true,
            ],
            'salario_escala' => [
                'label' => 'Salario',
                'type' => 'number',
                'required' => false,
                'grid' => true,
            ],
            'noct1' => [
                'label' => 'NOCT 7/11',
                'type' => 'number',
                'required' => false,
                'grid' => false,
            ],
            'noct2' => [
                'label' => 'NOCT 11/7',
                'type' => 'number',
                'required' => false,
                'grid' => false,
            ],
        ];
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean',
            'id_fondo_tiempo' => 'required|exists:fondos_tiempo,id',
            'id_nivel_educacion' => 'required|exists:tipos_nivel_educacion,id',
            'id_grupo_escala' => 'required|exists:grupos_escala,id',
            'id_categoria_cargo' => 'required|exists:categorias_cargo,id',
            'id_grupo_horario' => 'required|exists:tipos_grupo_horario,id',
            'tipo_salario' => 'required|in:0,1',
            'en_salario' => 'required|in:0,1',
            'tarifa' => 'nullable|numeric',
            'cla' => 'nullable|numeric',
            'salario_escala' => 'nullable|numeric',
            'noct1' => 'nullable|numeric',
            'noct2' => 'nullable|numeric',
        ];
    }

    public function index(Request $request)
    {
        $model = $this->getModelClass();

        $query = $model::with(['grupo_escala', 'categoria_cargo', 'grupo_horario']);

        $entidadId = (int) session('entidad_activa_id');
        if ($entidadId && $this->isEntityScoped()) {
            $this->applyEntityScope($query, $entidadId);
        }

        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy($this->getSortField())->paginate(20);

        $fields = array_merge(
            ['nombre' => $this->getNombreConfig()],
            $this->getExtraFields()
        );

        $gridFields = [];
        foreach ($fields as $key => $cfg) {
            if ($cfg['grid'] ?? true) {
                $gridFields[$key] = $cfg;
            }
        }

        return Inertia::render('Cargos/Index', [
            'title' => $this->getTitle(),
            'items' => $items,
            'filters' => $request->only('search'),
            'catalogConfig' => [
                'route' => $this->getRouteName(),
                'title' => $this->getTitle(),
                'codigoManual' => false,
                'fields' => $fields,
                'gridFields' => $gridFields,
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

        $data['codigo'] = $this->generarCodigo();
        $data['salario_escala'] = $this->calcularSalario($data);

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

        $data['salario_escala'] = $this->calcularSalario($data);

        $item->update($data);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    protected function calcularSalario(array $data): ?float
    {
        if (! empty($data['id_grupo_escala'])) {
            $grupo = GrupoEscala::find($data['id_grupo_escala']);
            if ($grupo && $grupo->salario !== null) {
                $salario = (float) $grupo->salario;
                $cla = (float) ($data['cla'] ?? 0);

                return round($salario + $cla, 2);
            }
        }

        return null;
    }

    private function comboFondosTiempo(): array
    {
        return FondoTiempo::orderBy('fondo_tiempo')
            ->get()
            ->map(fn ($f) => ['value' => $f->id, 'label' => (string) $f->fondo_tiempo])
            ->toArray();
    }

    private function comboNivelesEducacion(): array
    {
        return TipoNivelEducacion::where('activo', true)
            ->orderBy('abreviatura')
            ->get()
            ->map(fn ($n) => ['value' => $n->id, 'label' => $n->abreviatura ?? $n->nombre])
            ->toArray();
    }

    private function comboGruposEscala(): array
    {
        return GrupoEscala::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($g) => ['value' => $g->id, 'label' => $g->nombre])
            ->toArray();
    }

    private function comboCategorias(): array
    {
        return CategoriaCargo::where('activo', true)
            ->orderBy('abreviatura')
            ->get()
            ->map(fn ($c) => ['value' => $c->id, 'label' => $c->abreviatura ?? $c->nombre])
            ->toArray();
    }

    private function comboGruposHorario(): array
    {
        return TipoGrupoHorario::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($g) => ['value' => $g->id, 'label' => $g->nombre])
            ->toArray();
    }
}
