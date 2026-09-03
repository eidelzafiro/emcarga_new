<?php

namespace App\Http\Controllers;

use App\Models\CatalogoItem;
use App\Models\OtrosGasto;
use App\Models\Tractivo;
use App\Http\Controllers\Traits\EntidadScoping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class OtrosGastosController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $this->authorize('viewAny', OtrosGasto::class);
        $entidades = $this->entidadesPermitidas();
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) \Illuminate\Support\Carbon::parse($fechaOperaciones)->year;
        $mes = (int) \Illuminate\Support\Carbon::parse($fechaOperaciones)->month;

        $gastos = OtrosGasto::with(['tractivo', 'arrastre', 'tipoConcepto'])
            ->whereYear('fecha', $anio)->whereMonth('fecha', $mes)
            ->when($request->search, fn ($q, $s) => $q->where('numero', 'like', "%{$s}%")
                ->orWhereHas('tipoConcepto', fn ($t) => $t->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('tractivo', fn ($t) => $t->where('codigo', 'like', "%{$s}%")))
            ->when(! empty($entidades), fn ($q) => $q->whereHas('tractivo', fn ($t) => $t->whereIn('id_entidad', $entidades)))
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        $tractivos = Tractivo::select('id', 'codigo')
            ->when(! empty($entidades), fn ($q) => $q->whereIn('id_entidad', $entidades))
            ->orderBy('codigo')
            ->get();

        return Inertia::render('OtrosGastos/Index', [
            'title' => 'Otros Gastos',
            'otros_gastos' => $gastos,
            'tractivos' => $tractivos,
            'tipos_concepto' => \App\Support\Catalogos::opciones('tipos_conceptos'),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        \Log::warning('OtrosGastos.store INIT', [
            'user' => auth()->id(),
            'roles' => auth()->user()?->roles->pluck('name')->toArray(),
            'perfil_activo' => session('perfil_activo'),
            'data' => $request->all(),
        ]);

        try {
            $this->authorize('create', OtrosGasto::class);
        } catch (\Exception $e) {
            \Log::error('OtrosGastos.store AUTH FAIL', ['msg' => $e->getMessage()]);
            throw $e;
        }

        $validated = $request->validate([
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_concepto' => 'required|exists:catalogo_items,id',
            'fecha' => 'required|date',
            'monto_mn' => 'required|numeric|min:0',
            'monto_mlc' => 'required|numeric|min:0',
            'descripcion' => 'nullable|max:500',
        ]);

        $entidadTract = $this->entidadTractivo($validated['id_tractivo']);
        \Log::warning('OtrosGastos.store ENTIDAD', ['tractivo_id' => $validated['id_tractivo'], 'entidad' => $entidadTract]);
        $this->autorizarEntidad($entidadTract);

        $validated['numero'] = $this->siguienteNumero();
        $validated['id_user'] = auth()->id();
        $validated['concepto'] = CatalogoItem::find($validated['id_tipo_concepto'])?->nombre ?? '';

        OtrosGasto::create($validated);

        return redirect()->route('otros-gastos.index')->with('success', 'Gasto creado correctamente.');
    }

    public function update(Request $request, OtrosGasto $otrosGasto)
    {
        $this->authorize('update', $otrosGasto);
        $this->autorizarEntidad($this->entidadDelGasto($otrosGasto));

        $validated = $request->validate([
            'id_tractivo' => 'required|exists:tractivos,id',
            'id_tipo_concepto' => 'required|exists:catalogo_items,id',
            'fecha' => 'required|date',
            'monto_mn' => 'required|numeric|min:0',
            'monto_mlc' => 'required|numeric|min:0',
            'descripcion' => 'nullable|max:500',
        ]);

        $this->autorizarEntidad($this->entidadTractivo($validated['id_tractivo']));
        $validated['concepto'] = CatalogoItem::find($validated['id_tipo_concepto'])?->nombre ?? '';
        $otrosGasto->update($validated);

        return redirect()->route('otros-gastos.index')->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy(OtrosGasto $otrosGasto)
    {
        $this->authorize('delete', $otrosGasto);
        $this->autorizarEntidad($this->entidadDelGasto($otrosGasto));

        $otrosGasto->delete();

        return redirect()->route('otros-gastos.index')->with('success', 'Gasto eliminado correctamente.');
    }

    /**
     * Crear un tipo de concepto inline desde el combo.
     */
    public function storeTipoConcepto(Request $request)
    {
        $this->authorize('catalogo.crear');

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $maxCod = CatalogoItem::where('tipo', 'tipos_conceptos')
            ->selectRaw("MAX(CAST(codigo AS UNSIGNED)) as max_cod")
            ->value('max_cod');

        $item = CatalogoItem::create([
            'tipo' => 'tipos_conceptos',
            'nombre' => $validated['nombre'],
            'codigo' => str_pad((string) ((int) ($maxCod ?? 0) + 1), 2, '0', STR_PAD_LEFT),
            'activo' => true,
        ]);

        Cache::forget('catalogos.opciones.tipos_conceptos.0');

        return response()->json(['id' => $item->id, 'nombre' => $item->nombre]);
    }

    private function siguienteNumero(): string
    {
        $anio = (int) date('Y');
        $prefijo = 'OG-' . $anio . '-';
        $max = OtrosGasto::where('numero', 'like', $prefijo . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(numero, " . (strlen($prefijo) + 1) . ") AS UNSIGNED)) as max_num")
            ->value('max_num');

        return $prefijo . str_pad((string) ((int) ($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function entidadTractivo(int $idTractivo): ?int
    {
        return Tractivo::find($idTractivo)?->id_entidad;
    }

    private function entidadDelGasto(OtrosGasto $gasto): ?int
    {
        return $gasto->tractivo?->id_entidad;
    }
}
