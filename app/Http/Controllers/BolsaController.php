<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Cargo;
use App\Models\Area;
use App\Models\Entidad;
use App\Models\User;
use App\Services\NotificarDocumentosChofer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class BolsaController extends Controller
{
    public function index(Request $request)
    {
        $items = Bolsa::with(['cargo', 'area', 'entidad'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('nombre', 'like', "%{$s}%")
                    ->orWhere('apellidos', 'like', "%{$s}%")
                    ->orWhere('ci', 'like', "%{$s}%");
            }))
            ->when($request->id_cargo, fn ($q, $c) => $q->where('id_cargo', $c))
            ->when($request->id_area, fn ($q, $a) => $q->where('id_area', $a))
            ->when($entidadId = session('entidad_activa_id'), fn ($q) => $q->where('id_entidad', $entidadId))
            ->orderBy('nombre')
            ->paginate(20);

        $entidadActiva = (int) session('entidad_activa_id');

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
            'esSuperadmin' => $request->user()->hasRole('SUPERADMIN'),
            'filters' => $request->only(['search', 'id_cargo', 'id_area']),
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->user()->hasRole('SUPERADMIN')) {
            abort(403, 'Solo el SUPERADMIN puede modificar la bolsa.');
        }

        $validated = $request->validate($this->rules());

        $validated['id_entidad'] ??= session('entidad_activa_id');

        $bolsa = Bolsa::create($validated);

        if ($request->boolean('crear_usuario')) {
            $this->crearUsuario($bolsa, $request);
        }

        $this->notificarDocumentos($bolsa);

        return redirect()->route('bolsa.index')->with('success', 'Registro creado correctamente.');
    }

    public function update(Request $request, Bolsa $bolsa)
    {
        if (! $request->user()->hasRole('SUPERADMIN')) {
            abort(403, 'Solo el SUPERADMIN puede modificar la bolsa.');
        }

        $validated = $request->validate($this->rules($bolsa->id));

        $validated['id_entidad'] ??= session('entidad_activa_id');

        $bolsa->update($validated);

        $this->notificarDocumentos($bolsa);

        return redirect()->route('bolsa.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Request $request, Bolsa $bolsa)
    {
        if (! $request->user()->hasRole('SUPERADMIN')) {
            abort(403, 'Solo el SUPERADMIN puede modificar la bolsa.');
        }

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
            'sexo' => ['nullable', 'max:1'],
            'color_piel' => ['nullable', 'max:50'],
            'nivel_educacional' => ['nullable', 'max:100'],
            'estado_civil' => ['nullable', 'max:50'],
            'ubicacion_defensa' => ['nullable', 'max:200'],
            'tiene_licencia' => ['boolean'],
            'categorias_licencia' => ['nullable', 'max:100'],
            'licencia_emision' => ['nullable', 'date'],
            'licencia_vencimiento' => ['nullable', 'date'],
            'limitaciones' => ['nullable', 'string'],
            'chequeo_medico_emision' => ['nullable', 'date'],
            'chequeo_medico_vencimiento' => ['nullable', 'date'],
            'reubicacion_emision' => ['nullable', 'date'],
            'reubicacion_vencimiento' => ['nullable', 'date'],
            'psicometrico_emision' => ['nullable', 'date'],
            'psicometrico_vencimiento' => ['nullable', 'date'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'direccion' => ['nullable', 'max:500'],
            'telefono' => ['nullable', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'id_cargo' => ['nullable', 'exists:cargos,id'],
            'id_area' => ['nullable', 'exists:areas,id'],
            'id_entidad' => ['nullable', 'exists:entidades,id'],
        ];
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
            'id_entidad' => $bolsa->id_entidad ?? session('entidad_activa_id'),
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
