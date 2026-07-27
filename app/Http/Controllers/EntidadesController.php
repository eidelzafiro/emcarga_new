<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ManagesCatalog;
use App\Models\Entidad;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\TiposSistema;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntidadesController extends Controller
{
    use ManagesCatalog;

    protected function getModelClass(): string
    {
        return Entidad::class;
    }

    protected function getRouteName(): string
    {
        return 'entidades';
    }

    protected function getTitle(): string
    {
        return 'Entidades';
    }

    protected function getSortField(): string
    {
        return 'nombre';
    }

    protected function getSearchFields(): array
    {
        return ['codigo', 'nombre', 'abreviatura', 'direccion', 'nit'];
    }

    protected function getValidationRules($id = null): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'abreviatura' => 'nullable|string|max:150',
            'id_area' => 'nullable|exists:areas,id',
            'activo' => 'boolean',
            'direccion' => 'nullable|string|max:200',
            'id_provincia' => 'nullable|exists:provincias,id',
            'id_municipio' => 'nullable|exists:municipios,id',
            'email' => 'nullable|email|max:200',
            'nit' => 'nullable|string|max:150',
            'licencia' => 'nullable|string|max:100',
            'cta_unica' => 'nullable|string|max:150',
            'cta_mn' => 'nullable|string|max:150',
            'cta_me' => 'nullable|string|max:150',
            'agencia' => 'nullable|string|max:250',
            'minutos' => 'nullable|integer|min:0',
            'folio_fact' => 'nullable|integer|min:0',
            'almacenaje' => 'nullable|numeric|min:0|max:999.9999',
            'interruptos' => 'nullable|integer',
            'lugares' => 'nullable|integer|min:0',
            'pass_dias' => 'nullable|integer|min:0',
            'pass_cant_h' => 'nullable|integer|min:0',
            'notas_fact' => 'nullable|string',
            'mora_dias' => 'nullable|integer|min:0',
            'mora_porciento' => 'nullable|integer|min:0|max:100',
            'cliente_fincimex_mn' => 'nullable|string|max:20',
            'talon_versat' => 'nullable|string|max:10',
            'vida_bateria' => 'nullable|integer|min:0',
            'vida_neum_nuevo' => 'nullable|integer|min:0',
            'vida_neum_rec' => 'nullable|integer|min:0',
            'vida_neum_admin' => 'nullable|integer|min:0',
            'disponible' => 'boolean',
            'desactivar_disp' => 'boolean',
            'alertas_mtto' => 'boolean',
            'tipo_planificacion' => 'nullable|integer',
            'matriz' => 'nullable|integer',
            'tasas_aforo' => 'nullable|integer|min:0',
            'requisitos' => 'nullable|integer|min:0',
            'oper_carga' => 'nullable|integer|min:0',
            'descargas' => 'nullable|integer|min:0',
            'id_frecuencia' => 'nullable|integer',
            'id_sistema' => 'nullable|exists:tipos_sistemas,id',
            'id_cajera' => 'nullable|integer',
            'id_parqueo' => 'nullable|integer',
        ];
    }

    protected function getExtraFields(): array
    {
        $provincias = Provincia::orderBy('nombre')->get(['id', 'nombre']);
        $sistemas = TiposSistema::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);

        return [
            'abreviatura' => ['label' => 'Abreviatura', 'type' => 'text'],
            'direccion' => ['label' => 'Dirección', 'type' => 'textarea'],
            'id_provincia' => ['label' => 'Provincia', 'type' => 'select', 'options' => $provincias->map(fn ($p) => ['value' => $p->id, 'label' => $p->nombre])],
            'id_municipio' => ['label' => 'Municipio', 'type' => 'select', 'options' => []],
            'email' => ['label' => 'Correo electrónico', 'type' => 'email'],
            'nit' => ['label' => 'NIT', 'type' => 'text'],
            'licencia' => ['label' => 'Licencia', 'type' => 'text'],
            'cta_unica' => ['label' => 'Cuenta Única', 'type' => 'text'],
            'cta_mn' => ['label' => 'Cuenta MN', 'type' => 'text'],
            'cta_me' => ['label' => 'Cuenta ME', 'type' => 'text'],
            'agencia' => ['label' => 'Agencia Bancaria', 'type' => 'text'],
            'minutos' => ['label' => 'Minutos', 'type' => 'number'],
            'folio_fact' => ['label' => 'Folio Factura', 'type' => 'number'],
            'almacenaje' => ['label' => 'Almacenaje', 'type' => 'number'],
            'interruptos' => ['label' => 'Interruptos', 'type' => 'number'],
            'lugares' => ['label' => 'Lugares', 'type' => 'number'],
            'pass_dias' => ['label' => 'Pass Días', 'type' => 'number'],
            'pass_cant_h' => ['label' => 'Pass Cant H', 'type' => 'number'],
            'notas_fact' => ['label' => 'Notas Factura', 'type' => 'textarea'],
            'mora_dias' => ['label' => 'Mora Días', 'type' => 'number'],
            'mora_porciento' => ['label' => 'Mora Porciento', 'type' => 'number'],
            'cliente_fincimex_mn' => ['label' => 'Cliente Fincimex MN', 'type' => 'text'],
            'talon_versat' => ['label' => 'Talón Versat', 'type' => 'text'],
            'vida_bateria' => ['label' => 'Vida Batería (días)', 'type' => 'number'],
            'vida_neum_nuevo' => ['label' => 'Vida Neum Nuevo (días)', 'type' => 'number'],
            'vida_neum_rec' => ['label' => 'Vida Neum Rec (días)', 'type' => 'number'],
            'vida_neum_admin' => ['label' => 'Vida Neum Admin (días)', 'type' => 'number'],
            'disponible' => ['label' => 'Disponible', 'type' => 'boolean'],
            'desactivar_disp' => ['label' => 'Desactivar Disponible', 'type' => 'boolean'],
            'alertas_mtto' => ['label' => 'Alertas Mantenimiento', 'type' => 'boolean'],
            'tipo_planificacion' => ['label' => 'Tipo Planificación', 'type' => 'number'],
            'matriz' => ['label' => 'Matriz', 'type' => 'number'],
            'tasas_aforo' => ['label' => 'Tasas Aforo', 'type' => 'number'],
            'requisitos' => ['label' => 'Requisitos', 'type' => 'number'],
            'oper_carga' => ['label' => 'Operación Carga', 'type' => 'number'],
            'descargas' => ['label' => 'Descargas', 'type' => 'number'],
            'id_frecuencia' => ['label' => 'Frecuencia', 'type' => 'number'],
            'id_sistema' => ['label' => 'Sistema', 'type' => 'select', 'options' => $sistemas->map(fn ($s) => ['value' => $s->id, 'label' => $s->nombre])],
            'id_cajera' => ['label' => 'Cajera', 'type' => 'number'],
            'id_parqueo' => ['label' => 'Parqueo', 'type' => 'number'],
        ];
    }

    public function index(Request $request)
    {
        $model = $this->getModelClass();

        $query = $model::query();
        $search = $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                foreach ($this->getSearchFields() as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        return Inertia::render('Catalogo/Index', [
            'items' => $query->orderBy($this->getSortField())->paginate(20),
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
}
