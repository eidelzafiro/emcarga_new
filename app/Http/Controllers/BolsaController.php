<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\Area;
use App\Models\Entidad;
use App\Models\User;
use App\Http\Controllers\Traits\EntidadScoping;
use App\Services\NotificarDocumentosChofer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class BolsaController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\Bolsa::class);
        $items = Bolsa::with([
            'cargo', 'area', 'entidad', 'sexoCatalogo', 'colorPiel', 'nivelEducacional', 'estadoCivil', 'ubicacionDefensa',
            'documentos' => fn ($q) => $q->orderByDesc('vencimiento'),
            'licenciaCategorias',
        ])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nombre', 'like', "%{$s}%")
                    ->orWhere('apellidos', 'like', "%{$s}%")
                    ->orWhere('ci', 'like', "%{$s}%");
            }))
            ->when($request->id_cargo, fn ($q, $c) => $q->where('id_cargo', $c))
            ->when($request->id_area, fn ($q, $a) => $q->where('id_area', $a))
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereIn('id_entidad', $this->entidadesPermitidas()))
            ->orderBy('nombre')
            ->paginate(20);

        $entidadActiva = (int) entidadActivaId();

        $cargos = Cargo::query()
            ->when($entidadActiva, fn ($q) => $q->where('id_entidad', $entidadActiva))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
        $areas = Area::query()
            ->when($entidadActiva, fn ($q) => $q->where('id_entidad', $entidadActiva))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
        $entidades = Entidad::orderBy('nombre')->get(['id', 'nombre']);
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Bolsa/Index', [
            'title' => 'Bolsa',
            'bolsa' => $items,
            'cargos' => $cargos,
            'areas' => $areas,
            'entidades' => $entidades,
            'roles' => $roles,
            'catalogo' => $this->opcionesCatalogo(),
            'esSuperadmin' => $request->user()->hasRole('SUPERADMIN'),
            'filters' => $request->only(['search', 'id_cargo', 'id_area']),
        ]);
    }

    public function store(Request $request)
    {

        $this->authorize('create', \App\Models\Bolsa::class);if (! $request->user()->hasRole('SUPERADMIN')) {
            abort(403, 'Solo el SUPERADMIN puede modificar la bolsa.');
        }

        $validated = $request->validate($this->rules());

        $validated['id_entidad'] ??= entidadActivaId();

        $bolsa = Bolsa::create($validated);
        $this->syncDocumentos($bolsa, $request);

        if ($request->boolean('crear_usuario')) {
            $this->crearUsuario($bolsa, $request);
        }

        $this->notificarDocumentos($bolsa);

        return redirect()->route('bolsa.index')->with('success', 'Registro creado correctamente.');
    }

    public function update(Request $request, Bolsa $bolsa)
    {

        $this->authorize('update', $bolsa);if (! $request->user()->hasRole('SUPERADMIN')) {
            abort(403, 'Solo el SUPERADMIN puede modificar la bolsa.');
        }

        $this->autorizarEntidad($bolsa->id_entidad);

        $validated = $request->validate($this->rules($bolsa->id));

        $validated['id_entidad'] ??= entidadActivaId();

        $bolsa->update($validated);
        $this->syncDocumentos($bolsa, $request);

        $this->notificarDocumentos($bolsa);

        return redirect()->route('bolsa.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Request $request, Bolsa $bolsa)
    {
        
        $this->authorize('delete', $bolsa);if (! $request->user()->hasRole('SUPERADMIN')) {
            abort(403, 'Solo el SUPERADMIN puede modificar la bolsa.');
        }

        $this->autorizarEntidad($bolsa->id_entidad);

        $bolsa->delete();

        return redirect()->route('bolsa.index')->with('success', 'Registro eliminado correctamente.');
    }

    private function rules(?int $id = null): array
    {
        $uniqueCi = $id ? 'unique:bolsa,ci,'.$id : 'unique:bolsa,ci';

        return [
            'ci' => ['required', $uniqueCi, 'max:20'],
            'nombre' => ['required', 'max:255'],
            'apellidos' => ['required', 'max:255'],
            'sexo' => ['nullable', 'exists:catalogo_items,id'],
            'color_piel' => ['nullable', 'exists:catalogo_items,id'],
            'nivel_educacional' => ['nullable', 'exists:catalogo_items,id'],
            'estado_civil' => ['nullable', 'exists:catalogo_items,id'],
            'ubicacion_defensa' => ['nullable', 'exists:catalogo_items,id'],
            'direccion' => ['nullable', 'max:500'],
            'telefono' => ['nullable', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'id_cargo' => ['nullable', 'exists:cargos,id'],
            'id_area' => ['nullable', 'exists:areas,id'],
            'id_entidad' => ['nullable', 'exists:entidades,id'],
            // Documentos del chofer (tabla documentos_chofer).
            'documentos' => ['nullable', 'array'],
            'documentos.*.tipo' => ['required_with:documentos', 'in:LICENCIA,CHEQUEO_MEDICO,RECALIFICACION,PSICOMETRICO'],
            'documentos.*.numero' => ['nullable', 'max:50'],
            'documentos.*.emision' => ['nullable', 'date'],
            'documentos.*.vencimiento' => ['nullable', 'date'],
            'documentos.*.notas' => ['nullable', 'max:255'],
            'categorias_licencia' => ['nullable', 'array'],
            'categorias_licencia.*' => ['nullable', 'max:3'],
        ];
    }

    /**
     * Sincroniza los documentos del chofer (upsert por tipo) y las
     * categorías de licencia (pivote).
     */
    private function syncDocumentos(Bolsa $bolsa, Request $request): void
    {
        $documentos = $request->input('documentos', []);
        if (is_array($documentos)) {
            foreach ($documentos as $doc) {
                $tipo = $doc['tipo'] ?? null;
                if (! $tipo || ! in_array($tipo, \App\Models\DocumentoChofer::TIPOS, true)) {
                    continue;
                }

                $datos = [
                    'numero' => $doc['numero'] ?? null,
                    'emision' => $doc['emision'] ?: null,
                    'vencimiento' => $doc['vencimiento'] ?: null,
                    'notas' => $doc['notas'] ?? null,
                    'vigente' => true,
                    'id_entidad' => $bolsa->id_entidad,
                ];

                \App\Models\DocumentoChofer::updateOrCreate(
                    ['id_bolsa' => $bolsa->id, 'tipo' => $tipo],
                    $datos
                );
            }
        }

        $categorias = array_values(array_filter(array_map('trim', (array) $request->input('categorias_licencia', []))));
        if ($request->has('categorias_licencia')) {
            \App\Models\LicenciaCategoria::where('id_bolsa', $bolsa->id)->delete();
            foreach (array_unique($categorias) as $cat) {
                if ($cat !== '') {
                    \App\Models\LicenciaCategoria::create(['id_bolsa' => $bolsa->id, 'categoria' => mb_strtoupper($cat)]);
                }
            }
        }
    }

    /**
     * Opciones del catálogo unificado para los selects de la bolsa.
     */
    private function opcionesCatalogo(): array
    {
        $tipos = [
            'sexos' => 'tipos_sexo',
            'colores_piel' => 'tipos_color_piel',
            'niveles_educacion' => 'tipos_nivel_educacion',
            'estados_civiles' => 'tipos_estado_civil',
            'ubicaciones_defensa' => 'tipos_ubicacion_defensa',
        ];

        $out = [];
        foreach ($tipos as $clave => $tipo) {
            $out[$clave] = \App\Models\CatalogoItem::query()
                ->where('tipo', $tipo)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre'])
                ->map(fn ($i) => ['label' => $i->nombre, 'value' => $i->id])
                ->all();
        }

        return $out;
    }

    private function crearUsuario(Bolsa $bolsa, Request $request): void
    {
        $username = strtoupper($bolsa->ci);

        if (User::where('username', $username)->exists()) {
            return;
        }

        $user = User::create([
            'name' => trim($bolsa->nombre.' '.$bolsa->apellidos),
            'username' => $username,
            'email' => $bolsa->email ?? $username.'@zafiro.local',
            'password' => Hash::make('ZAFIRO'),
            'password_temporal' => true,
            'id_entidad' => $bolsa->id_entidad ?? entidadActivaId(),
            'activo' => true,
        ]);

        $rol = $request->input('rol', 'RECHUM');
        $user->assignRole($rol);
    }

    /**
     * Emite notificaciones si el trabajador es chofer y algún documento está
     * próximo a vencer o vencido (licencia, chequeo médico, recalificación, psicométrico).
     */
    private function notificarDocumentos(Bolsa $bolsa): void
    {
        app(NotificarDocumentosChofer::class)->ejecutar($bolsa->id);
    }
}
