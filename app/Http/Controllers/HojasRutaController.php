<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Grupo;
use App\Models\HojasRuta;
use App\Models\Lugare;
use App\Models\Tractivo;
use App\Services\HojasRutaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HojasRutaController extends Controller
{
    public function __construct(private readonly HojasRutaService $service) {}

    public function index(Request $request)
    {
        $entidadId = session('entidad_activa_id');

        $hojas = HojasRuta::with(['tractivo:id,codigo,id_entidad', 'arrastre:id,codigo', 'chofer:id,nombre,apellidos', 'chofer2:id,nombre,apellidos', 'entidad:id,nombre', 'parqueo:id,nombre', 'grupo:id,nombre'])
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q2) => $q2
                ->where('numero', 'like', "%{$s}%")
                ->orWhereHas('tractivo', fn ($c) => $c->where('codigo', 'like', "%{$s}%"))
                ->orWhereHas('chofer', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('chofer', fn ($c) => $c->where('apellidos', 'like', "%{$s}%"))))
            ->when($request->estado && $request->estado !== 'todas', function ($q) use ($request) {
                match ($request->estado) {
                    'abiertas' => $q->whereNull('fecha_cierre')->where('cancelada', false),
                    'cerradas' => $q->whereNotNull('fecha_cierre')->where('cancelada', false),
                    'canceladas' => $q->where('cancelada', true),
                };
            })
            ->orderByDesc('fecha_emision')
            ->paginate(20);

        return Inertia::render('HojasRuta/Index', [
            'title' => 'Hoja de Ruta',
            'hojas' => $hojas,
            'filters' => $request->only(['search', 'estado']),
            'catalogos' => [
                'tractivos' => Tractivo::select('id', 'codigo', 'id_entidad')->whereNull('fecha_baja')->orderBy('codigo')->get(),
                'arrastres' => Tractivo::select('id', 'codigo')->where('id_grupo', 8)->orderBy('codigo')->get(),
                'choferes' => Bolsa::select('id', 'nombre', 'apellidos')->where('activo', true)->orderBy('nombre')->get(),
                'lugares' => Lugare::select('id', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
                'grupos' => Grupo::select('id', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validarApertura($request);

        $this->service->abrir($datos, $request->user()->id);

        return back()->with('success', 'Hoja de Ruta abierta correctamente.');
    }

    public function update(Request $request, int $hoja)
    {
        if ($request->input('operacion') === 'cierre-con-siguiente') {
            $datos = $this->validarCierre($request, true);
            $nueva = $this->service->cerrarYCrearSiguiente($hoja, $datos, $request->user()->id);

            return back()->with('success', "Hoja de Ruta cerrada. Nueva HR: {$nueva->numero}.");
        }

        if ($request->input('operacion') === 'cierre') {
            $datos = $this->validarCierre($request, false);
            $this->service->cerrar($hoja, $datos);

            return back()->with('success', 'Hoja de Ruta cerrada correctamente.');
        }

        $datos = $this->validarModificacion($request);
        $this->service->modificar($hoja, $datos, $request->user()->id);

        return back()->with('success', 'Hoja de Ruta actualizada correctamente.');
    }

    public function destroy(Request $request, int $hoja)
    {
        if ($request->input('operacion') === 'cancelar') {
            $this->service->cancelar($hoja, $request->user()->id, session('fecha_operaciones'));

            return back()->with('success', 'Hoja de Ruta cancelada.');
        }

        $this->service->eliminar($hoja);

        return back()->with('success', 'Hoja de Ruta eliminada correctamente.');
    }

    private function validarApertura(Request $request): array
    {
        return $request->validate([
            'numero' => ['required', 'string', 'max:50'],
            'fecha_emision' => ['required', 'date'],
            'hora_emision' => ['nullable', 'string', 'max:15'],
            'id_tractivo' => ['nullable', 'exists:tractivos,id'],
            'id_arrastre' => ['nullable', 'exists:tractivos,id'],
            'id_chofer' => ['nullable', 'exists:bolsa,id'],
            'id_chofer2' => ['nullable', 'exists:bolsa,id'],
            'id_parqueo' => ['nullable', 'exists:lugares,id'],
            'id_grupo' => ['nullable', 'exists:grupos,id'],
            'kms_disponible' => ['nullable', 'numeric', 'min:0'],
            'kms_disponibles_adicionales' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function validarCierre(Request $request, bool $conSiguiente): array
    {
        $reglas = [
            'fecha_cierre' => ['required', 'date'],
            'hora_cierre' => ['nullable', 'string', 'max:15'],
            'kms_totales' => ['required', 'numeric', 'min:0'],
            'combustible_habilitado' => ['nullable', 'numeric', 'min:0'],
            'combustible_consumido' => ['nullable', 'numeric', 'min:0'],
            'combustible_tecnico' => ['nullable', 'numeric', 'min:0'],
            'dias_trabajados' => ['nullable', 'string', 'max:70'],
        ];

        if ($conSiguiente) {
            $reglas += [
                'numero_nueva' => ['required', 'string', 'max:50'],
                'fecha_emision' => ['required', 'date'],
                'hora_emision' => ['nullable', 'string', 'max:15'],
                'kms_disponible' => ['nullable', 'numeric', 'min:0'],
                'kms_disponibles_adicionales' => ['nullable', 'numeric', 'min:0'],
            ];
        }

        return $request->validate($reglas);
    }

    private function validarModificacion(Request $request): array
    {
        return $request->validate([
            'numero' => ['required', 'string', 'max:50'],
            'fecha_emision' => ['required', 'date'],
            'hora_emision' => ['nullable', 'string', 'max:15'],
            'fecha_cierre' => ['nullable', 'date'],
            'hora_cierre' => ['nullable', 'string', 'max:15'],
            'id_tractivo' => ['nullable', 'exists:tractivos,id'],
            'id_arrastre' => ['nullable', 'exists:tractivos,id'],
            'id_chofer' => ['nullable', 'exists:bolsa,id'],
            'id_chofer2' => ['nullable', 'exists:bolsa,id'],
            'id_parqueo' => ['nullable', 'exists:lugares,id'],
            'id_grupo' => ['nullable', 'exists:grupos,id'],
            'kms_disponible' => ['nullable', 'numeric', 'min:0'],
            'kms_disponibles_adicionales' => ['nullable', 'numeric', 'min:0'],
            'kms_totales' => ['nullable', 'numeric', 'min:0'],
            'combustible_habilitado' => ['nullable', 'numeric', 'min:0'],
            'combustible_consumido' => ['nullable', 'numeric', 'min:0'],
            'combustible_tecnico' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string'],
            'analisis' => ['nullable', 'string'],
            'tiempo_mov' => ['nullable', 'numeric', 'min:0'],
            'tiempo_espera' => ['nullable', 'numeric', 'min:0'],
            'tiempo_carga' => ['nullable', 'numeric', 'min:0'],
            'tiempo_taller' => ['nullable', 'numeric', 'min:0'],
            'tiempo_inactivo' => ['nullable', 'numeric', 'min:0'],
            'tiempo_otras_actividades' => ['nullable', 'numeric', 'min:0'],
            'tiempo_total' => ['nullable', 'numeric', 'min:0'],
            'dias_trabajados' => ['nullable', 'string', 'max:70'],
            'cancelada' => ['sometimes', 'boolean'],
        ]);
    }
}