<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EntidadScoping;
use App\Models\Bolsa;
use App\Models\Entidad;
use App\Models\Moneda;
use App\Models\Tarjeta;
use App\Models\TipoCombustible;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TarjetasController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        $tarjetas = Tarjeta::with(['moneda:id,codigo,nombre', 'tipoCombustible:id,nombre', 'empleado:id,nombre,apellidos', 'tractivo:id,codigo'])
            ->when($request->search, fn ($q, $s) => $q->where('numero', 'like', "%{$s}%")
                ->orWhereHas('empleado', fn ($q2) => $q2->where('nombre', 'like', "%{$s}%")->orWhere('apellidos', 'like', "%{$s}%")))
            ->when($request->estado, fn ($q, $v) => $q->where('estado', $v))
            ->when($request->id_tipo_combustible, fn ($q, $v) => $q->where('idtipocombustibles', $v))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderBy('numero')
            ->paginate(20);

        return Inertia::render('Tarjetas/Index', [
            'title' => 'Tarjetas',
            'tarjetas' => $tarjetas,
            'tiposCombustibles' => TipoCombustible::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'monedas' => Moneda::where('activo', true)->orderBy('codigo')->get(['id', 'codigo', 'nombre']),
            'filtros' => $this->filtros(),
            'filters' => $request->only(['search', 'estado', 'id_tipo_combustible']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $validated['id_entidad'] = (int) session('entidad_activa_id') ?: null;

        Tarjeta::create($validated);

        return redirect()->route('tarjetas.index')->with('success', 'Tarjeta creada correctamente.');
    }

    public function update(Request $request, Tarjeta $tarjeta)
    {
        $this->autorizarEntidad($tarjeta->id_entidad);

        $validated = $this->validar($request, $tarjeta);
        $tarjeta->update($validated);

        return redirect()->route('tarjetas.index')->with('success', 'Tarjeta actualizada correctamente.');
    }

    public function destroy(Tarjeta $tarjeta)
    {
        $this->autorizarEntidad($tarjeta->id_entidad);

        $tarjeta->delete();

        return redirect()->route('tarjetas.index')->with('success', 'Tarjeta eliminada correctamente.');
    }

    public function filtros()
    {
        $ids = $this->entidadesPermitidas();

        $empleados = Bolsa::select('id', 'nombre', 'apellidos')
            ->when(! empty($ids), fn ($q) => $q->whereIn('id_entidad', $ids))
            ->orderBy('nombre')
            ->get();

        $tractivos = Tractivo::select('id', 'codigo', 'descripcion')
            ->when(! empty($ids), fn ($q) => $q->whereIn('id_entidad', $ids))
            ->orderBy('codigo')
            ->get();

        return [
            'empleados' => $empleados,
            'tractivos' => $tractivos,
            'entidades' => Entidad::select('id', 'abreviatura')->whereIn('id', $ids)->orderBy('abreviatura')->get(),
        ];
    }

    private function validar(Request $request, ?Tarjeta $tarjeta = null): array
    {
        $unique = $tarjeta ? 'unique:tarjetas,numero,'.$tarjeta->id : 'unique:tarjetas,numero';

        return $request->validate([
            'numero' => 'required|'.$unique.'|max:50',
            'descripcion' => 'nullable|max:255',
            'saldo_actual' => 'nullable|numeric',
            'fcompra' => 'nullable|date',
            'fvence' => 'nullable|date',
            'saldoinicialmon' => 'nullable|numeric',
            'saldoiniciallts' => 'nullable|numeric',
            'saldoactuallts' => 'nullable|numeric',
            'saldotransferenciamon' => 'nullable|numeric',
            'saldotransferencialts' => 'nullable|numeric',
            'idmonedas' => 'nullable|exists:monedas,id',
            'idtipocombustibles' => 'nullable|exists:tipos_combustibles,id',
            'idempleado' => 'nullable|exists:bolsa,id',
            'idtractivos' => 'nullable|exists:tractivos,id',
            'idchofer' => 'nullable|exists:bolsa,id',
            'inactiva' => 'sometimes|boolean',
            'estado' => 'required|in:activa,inactiva,cancelada',
        ]);
    }
}
