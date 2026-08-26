<?php

namespace App\Http\Controllers;

use App\Models\CatalogoItem;
use App\Models\TipoArrastre;
use App\Models\TipoEquipo;
use App\Models\TipoVehiculo;
use App\Models\TiposMantenimiento;
use App\Models\TipoTractivo;
use App\Support\Catalogos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TipoVehiculoController extends Controller
{
    public function index(Request $request)
    {
        $query = TipoVehiculo::with([
            'tipoEquipo', 'marca', 'modelo', 'tipoMantenimiento',
            'tipoTractivo', 'tipoArrastre',
        ])
        ->selectRaw(
            'tipo_vehiculos.*, ' .
            '((SELECT COUNT(*) FROM tractivos WHERE tractivos.id_tipo_vehiculo = tipo_vehiculos.id) + ' .
            '(SELECT COUNT(*) FROM arrastres WHERE arrastres.id_tipo_vehiculo = tipo_vehiculos.id)) AS vehiculos_count'
        );

        if ($clase = $request->get('clase')) {
            $query->where('clase', $clase);
        }
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('marca', fn ($m) => $m->where('nombre', 'like', "%{$search}%"))
                  ->orWhereHas('modelo', fn ($m) => $m->where('nombre', 'like', "%{$search}%"))
                  ->orWhereHas('tipoEquipo', fn ($t) => $t->where('nombre', 'like', "%{$search}%"))
                  ->orWhere('fabricacion', 'like', "%{$search}%");
            });
        }

        $items = $query->orderByDesc('vehiculos_count')->orderBy('id')->paginate(20)
            ->withQueryString();

        return Inertia::render('TipoVehiculos/Index', [
            'title' => 'Tipos de Vehículo',
            'items' => $items,
            'filters' => $request->only(['clase', 'search']),
            'clases' => [
                ['value' => '', 'label' => 'Todos'],
                ['value' => 'tractivo', 'label' => 'Tractivo'],
                ['value' => 'arrastre', 'label' => 'Arrastre'],
            ],
            'options' => $this->comboOptions(),
        ]);
    }

    public function create()
    {
        return Inertia::render('TipoVehiculos/Form', [
            'title' => 'Nuevo Tipo de Vehículo',
            'tipoVehiculo' => null,
            'options' => $this->comboOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $tipo = DB::transaction(function () use ($data) {
            $idTractivo = $data['id_tipo_tractivo'] ?? null;
            $idArrastre = $data['id_tipo_arrastre'] ?? null;

            $nuevoTractivo = $this->guardarFicha('tractivo', $data, $idTractivo);
            $nuevoArrastre = $this->guardarFicha('arrastre', $data, $idArrastre);

            $tipoData = collect($data)
                ->except(['ficha_tractivo', 'ficha_arrastre', 'id_tipo_tractivo', 'id_tipo_arrastre'])
                ->all();
            $tipoData['id_tipo_tractivo'] = $nuevoTractivo ?? $idTractivo;
            $tipoData['id_tipo_arrastre'] = $nuevoArrastre ?? $idArrastre;

            return TipoVehiculo::create($tipoData);
        });

        return redirect()->route('tipo-vehiculos.index')
            ->with('success', 'Tipo de vehículo creado.');
    }

    public function edit(TipoVehiculo $tipoVehiculo)
    {
        return Inertia::render('TipoVehiculos/Form', [
            'title' => 'Editar Tipo de Vehículo',
            'tipoVehiculo' => $tipoVehiculo->load([
                'tipoEquipo', 'marca', 'modelo', 'tipoMantenimiento',
                'tipoTractivo', 'tipoArrastre',
            ]),
            'options' => $this->comboOptions(),
        ]);
    }

    public function update(Request $request, TipoVehiculo $tipoVehiculo)
    {
        $data = $request->validate($this->rules($tipoVehiculo->id));

        DB::transaction(function () use ($tipoVehiculo, $data) {
            $idTractivo = $data['id_tipo_tractivo'] ?? null;
            $idArrastre = $data['id_tipo_arrastre'] ?? null;

            $nuevoTractivo = $this->guardarFicha('tractivo', $data, $idTractivo);
            $nuevoArrastre = $this->guardarFicha('arrastre', $data, $idArrastre);

            $tipoData = collect($data)
                ->except(['ficha_tractivo', 'ficha_arrastre', 'id_tipo_tractivo', 'id_tipo_arrastre'])
                ->all();
            $tipoData['id_tipo_tractivo'] = $nuevoTractivo ?? $idTractivo;
            $tipoData['id_tipo_arrastre'] = $nuevoArrastre ?? $idArrastre;

            $tipoVehiculo->update($tipoData);
        });

        return redirect()->route('tipo-vehiculos.index')
            ->with('success', 'Tipo de vehículo actualizado.');
    }

    public function destroy(TipoVehiculo $tipoVehiculo)
    {
        if ($tipoVehiculo->tractivos()->exists() || $tipoVehiculo->arrastres()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene vehículos asociados.');
        }

        $tipoVehiculo->delete();

        return back()->with('success', 'Tipo de vehículo eliminado.');
    }

    private function rules(?int $id = null): array
    {
        return [
            'clase' => ['required', 'in:tractivo,arrastre'],
            'id_tipo_equipo' => ['nullable', 'exists:tipos_equipos,id'],
            'id_marca' => ['nullable', 'exists:catalogo_items,id'],
            'id_modelo' => ['nullable', 'exists:catalogo_items,id'],
            'id_tipo_mantenimiento' => ['nullable', 'exists:tipos_mantenimiento,id'],
            'fabricacion' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'id_tipo_tractivo' => ['nullable', 'exists:tipos_tractivos,id'],
            'id_tipo_arrastre' => ['nullable', 'exists:tipos_arrastres,id'],
            'activo' => ['boolean'],
        ];

        // Campos editables de la ficha técnica (tipos_tractivos / tipos_arrastres)
        foreach ((new TiposTractivosController())->getExtraFields() as $key => $cfg) {
            $rules['ficha_tractivo.' . $key] = match ($cfg['type'] ?? 'text') {
                'number' => 'nullable|numeric',
                'boolean' => 'boolean',
                default => 'nullable|string|max:255',
            };
        }
        foreach ((new TiposArrastresController())->getExtraFields() as $key => $cfg) {
            $rules['ficha_arrastre.' . $key] = match ($cfg['type'] ?? 'text') {
                'number' => 'nullable|numeric',
                'boolean' => 'boolean',
                default => 'nullable|string|max:255',
            };
        }

        return $rules;
    }

    private function comboOptions(): array
    {
        $cat = fn (string $tipo) => collect(Catalogos::opciones($tipo))
            ->map(fn ($o) => ['value' => (int) $o['id'], 'label' => (string) $o['nombre']])
            ->values()->all();

        return [
            'tipo_equipo' => TipoEquipo::where('activo', true)->orderBy('nombre')
                ->get(['id', 'nombre'])->map(fn ($f) => ['value' => (int) $f->id, 'label' => $f->nombre])->all(),
            'marcas' => $cat('marcas'),
            'modelos' => $cat('modelos'),
            'tipo_mantenimiento' => TiposMantenimiento::where('activo', true)->orderBy('nombre')
                ->get(['id', 'nombre'])->map(fn ($f) => ['value' => (int) $f->id, 'label' => $f->nombre])->all(),
            'tipos_tractivos' => TipoTractivo::orderBy('id')->get()->map(fn ($f) => [
                'value' => (int) $f->id,
                'label' => 'Tractivo #' . $f->id,
                'ficha' => $f->toArray(),
            ])->all(),
            'tipos_arrastres' => TipoArrastre::orderBy('id')->get()->map(fn ($f) => [
                'value' => (int) $f->id,
                'label' => 'Arrastre #' . $f->id,
                'ficha' => $f->toArray(),
            ])->all(),
            'ficha_tractivo' => $this->fichaCampos('tractivo'),
            'ficha_arrastre' => $this->fichaCampos('arrastre'),
        ];
    }

    /**
     * Definición de campos editables de la ficha técnica, filtrada a las
     * columnas reales de la tabla (se excluyen campos calculados como id_pais).
     */
    private function fichaCampos(string $tipo): array
    {
        $ctrl = $tipo === 'tractivo' ? new TiposTractivosController() : new TiposArrastresController();
        $model = $tipo === 'tractivo' ? new TipoTractivo() : new TipoArrastre();
        $fillable = $model->getFillable();

        return collect($ctrl->getExtraFields())
            ->filter(fn ($cfg, $key) => in_array($key, $fillable, true))
            ->map(fn ($cfg, $key) => [
                'label' => $cfg['label'] ?? $key,
                'type' => $cfg['type'] ?? 'text',
                'options' => $cfg['options'] ?? [],
            ])->all();
    }

    /**
     * Crea o actualiza la ficha técnica (tipos_tractivos / tipos_arrastres)
     * asociada al tipo de vehículo. Devuelve el id de la ficha o null.
     */
    private function guardarFicha(string $tipo, array $data, ?int $existingId): ?int
    {
        if (($data['clase'] ?? null) !== $tipo) {
            return null;
        }

        $campos = collect($data['ficha_' . $tipo] ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->all();

        if (empty($campos)) {
            return null;
        }

        $model = $tipo === 'tractivo'
            ? ($existingId ? TipoTractivo::findOrFail($existingId) : new TipoTractivo())
            : ($existingId ? TipoArrastre::findOrFail($existingId) : new TipoArrastre());

        $model->fill(collect($campos)->only($model->getFillable())->all());
        $model->save();

        return $model->id;
    }
}
