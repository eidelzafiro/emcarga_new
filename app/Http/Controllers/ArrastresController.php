<?php

namespace App\Http\Controllers;

use App\Models\Arrastre;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Módulo "Arrastres".
 *
 * Fuente de datos ÚNICA: la tabla `arrastres` (modelo Arrastre). Los arrastres
 * ya NO viven en `tractivos`; son filas propias con el subconjunto de campos
 * definido por el usuario: codigo, placa, id_tipo_vehiculo, indice_aceite,
 * tara, id_color_primario, id_color_secundario, estado, fecha_alta,
 * fecha_baja, id_entidad.
 */
class ArrastresController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $this->authorize('viewAny', [\App\Models\Tractivo::class, \App\Policies\ArrastrePolicy::class]);
        $query = Arrastre::query();

        $entidades = $this->entidadesPermitidas();
        if (! empty($entidades)) {
            $query->whereIn('id_entidad', $entidades);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('codigo', 'like', "%{$request->search}%")
                    ->orWhere('placa', 'like', "%{$request->search}%");
            });
        }

        $items = $query->orderBy('placa')->paginate(20)->withQueryString();

        $items->getCollection()->transform(function ($arrastre) {
            $tipo = collect($this->tiposTipoArrastre())->firstWhere('value', $arrastre->id_tipo_vehiculo);
            $arrastre->tipo_vehiculo_label = $tipo['label'] ?? ('Tipo '.$arrastre->id_tipo_vehiculo);

            return $arrastre;
        });

        return Inertia::render('Arrastres/Index', [
            'title' => 'Arrastres',
            'items' => $items,
            'filters' => $request->only('search'),
            'catalogos' => [
                'tiposArrastre' => $this->tiposTipoArrastre(),
                'colores' => \App\Models\CatalogoItem::where('tipo', 'colores')->orderBy('nombre')->get(['id', 'nombre']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', [\App\Models\Tractivo::class, \App\Policies\ArrastrePolicy::class]);
        $validated = $request->validate($this->reglas());

        $validated['id_entidad'] = (int) entidadActivaId();
        Arrastre::create($validated);

        return redirect()->route('arrastres.index')
            ->with('success', 'Arrastre creado correctamente.');
    }

    public function update(Request $request, Arrastre $arrastre)
    {
        $this->authorize('update', [$arrastre, \App\Policies\ArrastrePolicy::class]);
        $this->autorizarEntidad($arrastre->id_entidad);

        $validated = $request->validate($this->reglas($arrastre->id));
        $arrastre->update($validated);

        return redirect()->route('arrastres.index')
            ->with('success', 'Arrastre actualizado correctamente.');
    }

    public function destroy(Arrastre $arrastre)
    {
        $this->authorize('delete', [$arrastre, \App\Policies\ArrastrePolicy::class]);
        $this->autorizarEntidad($arrastre->id_entidad);

        $arrastre->delete();

        return redirect()->route('arrastres.index')
            ->with('success', 'Arrastre eliminado correctamente.');
    }

    private function reglas(?int $id = null): array
    {
        return [
            'codigo' => 'nullable|string|max:50',
            'placa' => 'required|string|max:50|unique:arrastres,placa'.($id ? ','.$id : ''),
            'id_tipo_vehiculo' => 'nullable|exists:tipo_vehiculos,id',
            'tara' => 'nullable|numeric',
            'indice_aceite' => 'nullable|numeric',
            'id_color_primario' => 'nullable|exists:catalogo_items,id',
            'id_color_secundario' => 'nullable|exists:catalogo_items,id',
            'estado' => 'nullable|string|max:50',
            'fecha_alta' => 'nullable|date',
            'fecha_baja' => 'nullable|date',
        ];
    }

    private function tiposTipoArrastre(): array
    {
        return \App\Models\TipoVehiculo::with(['marca', 'modelo', 'tipoArrastre'])
            ->where('clase', 'arrastre')
            ->orderBy('id')
            ->get()
            ->map(function ($tv) {
                $marca = $tv->marca?->nombre;
                $modelo = $tv->modelo?->nombre;
                $anio = $tv->fabricacion;
                $partes = array_filter([$marca, $modelo, $anio]);
                $etiqueta = $partes ? implode(' - ', $partes) : ('Tipo '.$tv->id);

                return [
                    'value' => $tv->id,
                    'label' => $etiqueta,
                ];
            })
            ->values()
            ->toArray();
    }
}
