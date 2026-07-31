<?php

namespace App\Http\Controllers;

use App\Models\Entidad;
use App\Models\Municipio;
use App\Models\Provincia;
use App\Models\TipoSistema;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntidadesController extends Controller
{
    public function index(Request $request)
    {
        $query = Entidad::query();
        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%")
                    ->orWhere('abreviatura', 'like', "%{$search}%")
                    ->orWhere('direccion', 'like', "%{$search}%")
                    ->orWhere('nit', 'like', "%{$search}%");
            });
        }

        $provincias = Provincia::orderBy('nombre')->get(['id', 'nombre']);
        $municipios = Municipio::orderBy('nombre')->get(['id', 'nombre', 'id_provincia']);
        $sistemas = TipoSistema::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $entidadesPadre = Entidad::orderBy('nombre')->get(['id', 'codigo', 'nombre', 'abreviatura']);

        return Inertia::render('Entidades/Index', [
            'title' => 'Entidades',
            'items' => $query->orderBy('nombre')->paginate(20),
            'filters' => $request->only('search'),
            'provincias' => $provincias,
            'municipios' => $municipios,
            'sistemas' => $sistemas,
            'entidadesPadre' => $entidadesPadre,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        Entidad::create($data);

        return redirect()->back()->with('success', 'Creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $item = Entidad::findOrFail($id);
        $data = $request->validate($this->rules($id));
        $item->update($data);

        return redirect()->back()->with('success', 'Actualizado correctamente');
    }

    public function destroy($id)
    {
        $item = Entidad::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Eliminado correctamente');
    }

    private function rules($id = null): array
    {
        return [
            'codigo' => 'nullable|string|max:50',
            'nombre' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:entidades,id',
            'es_matriz' => 'boolean',
            'abreviatura' => 'nullable|string|max:150',
            'activo' => 'boolean',
            'direccion' => 'nullable|string|max:200',
            'id_provincia' => 'nullable|exists:provincias,id',
            'id_municipio' => 'nullable|exists:municipios,id',
            'email' => 'nullable|email|max:200',
            'nit' => 'nullable|string|max:150',
            'cta_unica' => 'nullable|string|max:150',
            'cta_mn' => 'nullable|string|max:150',
            'agencia' => 'nullable|string|max:250',
            'cliente_fincimex_mn' => 'nullable|string|max:20',
            'talon_versat' => 'nullable|string|max:10',
            'notas_fact' => 'nullable|string',
            'mora_dias' => 'nullable|integer|min:0',
            'mora_porciento' => 'nullable|integer|min:0|max:100',
            'id_cajera' => 'nullable|integer',
            'id_parqueo' => 'nullable|integer',
            'pass_dias' => 'nullable|integer|min:0',
            'pass_cant_h' => 'nullable|integer|min:0',
            'almacenaje' => 'nullable|numeric|min:0|max:999.9999',
            'minutos' => 'nullable|integer',
            'interruptos' => 'nullable|integer',
            'lugares' => 'nullable|integer|min:0',
            'oper_carga' => 'nullable|integer|min:0',
            'disponible' => 'nullable|integer',
            'tipo_planificacion' => 'nullable|integer',
            'tasas_aforo' => 'nullable|integer|min:0',
            'requisitos' => 'nullable|integer|min:0',
            'matriz' => 'nullable|integer',
            'id_sistema' => 'nullable|exists:tipos_sistemas,id',
            'licencia_vencimiento' => 'nullable|date',
            'licencia_activa' => 'boolean',
        ];
    }
}
