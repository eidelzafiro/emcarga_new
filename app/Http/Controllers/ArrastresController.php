<?php

namespace App\Http\Controllers;

use App\Models\Tractivo;
use App\Models\Grupo;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Módulo "Arrastres".
 *
 * Fuente de datos ÚNICA: la tabla `tractivos` filtrando por id_grupo=8
 * (grupo ARRASTRES). El arrastre NO guarda marca/modelo/año propios: su
 * ficha se hereda del "tipo de arrastre" (tipos_arrastres), igual que los
 * tractores heredan de tipos_tractivos.
 *
 * La tabla física `arrastres` se conserva como capa legacy (referenciada por
 * arrastre_tractivo) pero NO es la fuente de este módulo.
 */
class ArrastresController extends Controller
{
    public function index(Request $request)
    {
        $query = Tractivo::query()->where('id_grupo', 8);

        $entidadId = (int) session('entidad_activa_id');
        if ($entidadId) {
            $query->where('id_entidad', $entidadId);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('descripcion', 'like', "%{$request->search}%")
                    ->orWhere('placa', 'like', "%{$request->search}%");
            });
        }

        $items = $query->orderBy('descripcion')->paginate(20)->withQueryString();

        $items->getCollection()->transform(function ($tractivo) {
            $tipo = collect($this->combosTipoArrastre())->firstWhere('value', $tractivo->id_tipo_vehiculo);
            $tractivo->tipo_vehiculo_label = $tipo['label'] ?? ('Tipo '.$tractivo->id_tipo_vehiculo);
            $tractivo->tipo_ficha = $tipo['ficha'] ?? null;

            return $tractivo;
        });

        return Inertia::render('Arrastres/Index', [
            'title' => 'Arrastres',
            'items' => $items,
            'filters' => $request->only('search'),
            'catalogos' => [
                'tiposArrastre' => $this->tiposTipoArrastre(),
                'grupos' => [$this->grupoArrastre()],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas());

        $validated['id_entidad'] = (int) session('entidad_activa_id');
        $validated['id_grupo'] = 8;
        $this->aplicarFichaTipo($validated);
        Tractivo::create($validated);

        return redirect()->route('arrastres.index')
            ->with('success', 'Arrastre creado correctamente.');
    }

    public function update(Request $request, Tractivo $tractivo)
    {
        if ((int) $tractivo->id_grupo !== 8) {
            abort(404);
        }

        $validated = $request->validate($this->reglas($tractivo->id));
        $this->aplicarFichaTipo($validated);
        $tractivo->update($validated);

        return redirect()->route('arrastres.index')
            ->with('success', 'Arrastre actualizado correctamente.');
    }

    public function destroy(Tractivo $tractivo)
    {
        if ((int) $tractivo->id_grupo !== 8) {
            abort(404);
        }

        $tractivo->delete();

        return redirect()->route('arrastres.index')
            ->with('success', 'Arrastre eliminado correctamente.');
    }

    private function reglas(?int $id = null): array
    {
        return [
            'descripcion' => 'required|string|max:255',
            'placa' => 'required|string|max:50|unique:tractivos,placa'.($id ? ','.$id : ''),
            'id_tipo_vehiculo' => 'nullable|exists:tipos_arrastres,id',
            'capacidad_toneladas' => 'nullable|numeric',
            'lot' => 'nullable|string|max:100',
            'circulacion' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:50',
        ];
    }

    /**
     * Carga en el payload la marca/modelo/año heredados del tipo de arrastre.
     */
    private function aplicarFichaTipo(array &$datos): void
    {
        $idTipo = $datos['id_tipo_vehiculo'] ?? null;
        if (! $idTipo) {
            $datos['marca'] = null;
            $datos['modelo'] = null;
            $datos['anno'] = null;

            return;
        }

        $tipo = collect($this->tiposTipoArrastre())->firstWhere('value', $idTipo);
        $ficha = $tipo['ficha'] ?? null;
        $datos['marca'] = $ficha['marca'] ?? null;
        $datos['modelo'] = $ficha['modelo'] ?? null;
        $datos['anno'] = $ficha['anno'] ?? null;
    }

    private function tiposTipoArrastre(): array
    {
        return \App\Models\TipoArrastre::with(['marca', 'modelo'])
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                $marca = $item->marca?->nombre;
                $modelo = $item->modelo?->nombre;
                $anio = $item->fabricacion;
                $partes = array_filter([$marca, $modelo, $anio]);
                $etiqueta = $partes ? implode(' - ', $partes) : ('Tipo '.$item->id);

                return [
                    'value' => $item->id,
                    'label' => $etiqueta,
                    'ficha' => ['marca' => $marca, 'modelo' => $modelo, 'anno' => $anio],
                ];
            })
            ->values()
            ->toArray();
    }

    private function combosTipoArrastre(): array
    {
        return $this->tiposTipoArrastre();
    }

    private function grupoArrastre(): array
    {
        $grupo = \App\Models\Grupo::where('nombre', 'LIKE', '%ARRASTRE%')->first();

        return ['value' => $grupo?->id ?? 8, 'label' => $grupo?->nombre ?? 'Arrastres'];
    }
}