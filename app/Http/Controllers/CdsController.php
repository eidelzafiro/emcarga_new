<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\CatalogoItem;
use App\Models\CdsEntidad;
use App\Models\Entidad;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * CRUD del coeficiente CDS (Sistema de Pago por Resultados) por
 * entidad+mes+año+tipo de sistema de pago. Lo introduce manualmente el
 * cliente cada mes.
 */
class CdsController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $items = CdsEntidad::query()
            ->with(['entidad:id,nombre,abreviatura', 'sistemaPago:id,nombre'])
            ->when($request->ano, fn ($q, $v) => $q->where('ano', $v))
            ->when($request->mes, fn ($q, $v) => $q->where('mes', $v))
            ->when(! empty($this->entidadesPermitidas()),
                fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderByDesc('ano')
            ->orderByDesc('mes')
            ->orderBy('id_entidad')
            ->orderBy('id_tipo_sistema_pago')
            ->paginate(50);

        return Inertia::render('Cds/Index', [
            'title' => 'Coeficiente CDS',
            'items' => $items,
            'filters' => $request->only(['ano', 'mes']),
            'sistemasPago' => CatalogoItem::query()
                ->where('tipo', 'tipos_sistemas_pago')
                ->where('activo', true)
                ->orderBy('origen_id')
                ->get(['id', 'nombre'])
                ->map(fn ($s) => ['id' => $s->id, 'nombre' => $s->nombre])
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $validated['id_entidad'] = (int) entidadActivaId() ?: null;
        $validated['id_user'] = $request->user()->id;

        CdsEntidad::updateOrCreate(
            [
                'id_entidad' => $validated['id_entidad'],
                'mes' => $validated['mes'],
                'ano' => $validated['ano'],
                'id_tipo_sistema_pago' => $validated['id_tipo_sistema_pago'],
            ],
            ['cds' => $validated['cds'], 'id_user' => $validated['id_user']]
        );

        return redirect()->route('cds.index')->with('success', 'CDS guardado correctamente.');
    }

    public function update(Request $request, CdsEntidad $cds)
    {
        $this->autorizarEntidad($cds->id_entidad);
        $validated = $this->validar($request);

        $cds->update([
            'mes' => $validated['mes'],
            'ano' => $validated['ano'],
            'id_tipo_sistema_pago' => $validated['id_tipo_sistema_pago'],
            'cds' => $validated['cds'],
            'id_user' => $request->user()->id,
        ]);

        return redirect()->route('cds.index')->with('success', 'CDS actualizado correctamente.');
    }

    public function destroy(CdsEntidad $cds)
    {
        $this->autorizarEntidad($cds->id_entidad);
        $cds->delete();

        return redirect()->route('cds.index')->with('success', 'CDS eliminado correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'ano' => 'required|integer|min:2000|max:2100',
            'id_tipo_sistema_pago' => 'required|integer|exists:catalogo_items,id',
            'cds' => 'required|numeric|min:0',
        ]);
    }
}
