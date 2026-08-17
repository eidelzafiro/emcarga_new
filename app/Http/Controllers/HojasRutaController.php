<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Entidad;
use App\Models\Grupo;
use App\Models\HojasRuta;
use App\Models\Lugare;
use App\Models\Tractivo;
use App\Http\Controllers\Traits\EntidadScoping;
use App\Services\HojasRutaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Inertia\Inertia;

class HojasRutaController extends Controller
{
    use EntidadScoping;

    public function __construct(private readonly HojasRutaService $service) {}

    public function index(Request $request)
    {
        $entidadId = session('entidad_activa_id');

        // Fecha de operaciones → ventana de vigencia por mes
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $inicioMes = Carbon::parse($fechaOperaciones)->startOfMonth()->toDateString();
        $finMes = Carbon::parse($fechaOperaciones)->endOfMonth()->toDateString();

        $hojas = HojasRuta::with(['tractivo:id,codigo,id_entidad,id_grupo,indice_consumo', 'arrastre:id,codigo', 'chofer:id,nombre,apellidos,ci,categorias_licencia', 'chofer2:id,nombre,apellidos,ci,categorias_licencia', 'entidad:id,nombre', 'parqueo:id,nombre', 'grupo:id,nombre', 'cartasPorte' => fn ($q) => $q->where('estado', '!=', 'cancelada')->select('id', 'id_hoja_ruta', 'numero', 'estado', 'imprimir')])
            ->withCount(['cartasPorte' => fn ($q) => $q->where('estado', '!=', 'cancelada')])
            // Entidad activa por el tractivo de la hoja de ruta
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('tractivo', fn ($t) => $t->whereIn('id_entidad', $this->entidadesPermitidas())))
            // Vigentes: sin cierre o cerradas dentro del mes de operaciones
            ->where(fn ($q) => $q
                ->whereNull('fecha_cierre')
                ->orWhereBetween('fecha_cierre', [$inicioMes, $finMes]))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q2) => $q2
                ->where('numero', 'like', "%{$s}%")
                ->orWhereHas('tractivo', fn ($c) => $c->where('codigo', 'like', "%{$s}%"))
                ->orWhereHas('chofer', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('chofer', fn ($c) => $c->where('apellidos', 'like', "%{$s}%"))))
            ->when($request->equipo, fn ($q, $v) => $q->where('id_tractivo', $v))
            ->when($request->chofer, fn ($q, $v) => $q->where('id_chofer', $v))
            ->when($request->estado && $request->estado !== 'todas', function ($q) use ($request) {
                match ($request->estado) {
                    'abiertas' => $q->whereNull('fecha_cierre')->where('cancelada', false),
                    'cerradas' => $q->whereNotNull('fecha_cierre')->where('cancelada', false),
                    'canceladas' => $q->where('cancelada', true),
                };
            })
            ->when($request->grupo, fn ($q, $v) => $q->where('id_grupo', $v))
            ->orderByDesc('fecha_emision')
            ->paginate(20);

        $hoy = now()->toDateString();

        // Parqueo por defecto según la entidad activa (para preseleccionar en apertura)
        $entidadDefault = $entidadId ? Entidad::find($entidadId) : null;

        $catalogos = [
            'parqueo_default' => $entidadDefault?->id_parqueo ?? null,
            // Equipos (tractores): incluye tipo, marca y chapa para la vista read-only
            'tractivos' => Tractivo::with('grupo:id,nombre')
                ->select('id', 'codigo', 'id_entidad', 'marca', 'modelo', 'placa', 'id_grupo', 'kms_disp', 'indice_consumo')
                ->whereNull('fecha_baja')
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderBy('codigo')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'codigo' => $t->codigo,
                    'id_entidad' => $t->id_entidad,
                    'marca' => $t->marca,
                    'placa' => $t->placa,
                    'tipo' => $t->grupo?->nombre,
                    'id_grupo' => $t->id_grupo,
                    'kms_disp' => $t->kms_disp,
                    'indice_consumo' => $t->indice_consumo,
                ]),
            'arrastres' => Tractivo::with('grupo:id,nombre')
                ->select('id', 'codigo', 'marca', 'modelo', 'placa', 'id_grupo', 'kms_disp')
                ->where('id_grupo', 8)
                ->whereNull('fecha_baja')
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderBy('codigo')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'codigo' => $t->codigo,
                    'marca' => $t->marca,
                    'placa' => $t->placa,
                    'tipo' => $t->grupo?->nombre,
                    'kms_disp' => $t->kms_disp,
                ]),
            // Choferes: solo con licencia de conducción válida
            'choferes' => Bolsa::select('id', 'nombre', 'apellidos', 'ci', 'categorias_licencia', 'licencia_vencimiento')
                ->where('activo', true)
                ->where('tiene_licencia', true)
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->where(fn ($q) => $q->whereNull('licencia_vencimiento')->orWhere('licencia_vencimiento', '>=', $hoy))
                ->orderBy('nombre')
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'nombre' => $b->nombre,
                    'apellidos' => $b->apellidos,
                    'ci' => $b->ci,
                    'categorias_licencia' => $b->categorias_licencia,
                ]),
            'lugares' => Lugare::select('id', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'grupos' => Grupo::select('id', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            // HR anteriores: cerradas no canceladas de la entidad activa (para autocompletar apertura)
            'hojasAnteriores' => HojasRuta::with([
                'tractivo:id,codigo,id_entidad',
                'arrastre:id,codigo',
                'chofer:id,nombre,apellidos',
                'chofer2:id,nombre,apellidos',
                'parqueo:id,nombre',
                'grupo:id,nombre',
            ])
                ->select('id', 'numero', 'fecha_emision', 'fecha_cierre', 'hora_cierre', 'id_tractivo', 'id_arrastre', 'id_chofer', 'id_chofer2', 'id_parqueo', 'id_grupo', 'kms_disponible')
            ->whereNotNull('fecha_cierre')
            ->where('cancelada', false)
            ->when($entidadId, fn ($q) => $q->whereHas('tractivo', fn ($t) => $t->where('id_entidad', $entidadId)))
            ->orderByDesc('fecha_cierre')
            ->limit(50)
            ->get()
            ->map(fn ($hr) => [
                'id' => $hr->id,
                'numero' => $hr->numero,
                'fecha_cierre' => $hr->fecha_cierre,
                'hora_cierre' => $hr->hora_cierre,
                'id_tractivo' => $hr->id_tractivo,
                'id_arrastre' => $hr->id_arrastre,
                'id_chofer' => $hr->id_chofer,
                'id_chofer2' => $hr->id_chofer2,
                'id_parqueo' => $hr->id_parqueo,
                'id_grupo' => $hr->id_grupo,
                'kms_disponible' => $hr->kms_disponible,
                'tractivo_codigo' => $hr->tractivo?->codigo,
                'arrastre_codigo' => $hr->arrastre?->codigo,
                'chofer_nombre' => $hr->chofer ? trim($hr->chofer->nombre . ' ' . $hr->chofer->apellidos) : null,
                'chofer2_nombre' => $hr->chofer2 ? trim($hr->chofer2->nombre . ' ' . $hr->chofer2->apellidos) : null,
                'parqueo_nombre' => $hr->parqueo?->nombre,
                'grupo_nombre' => $hr->grupo?->nombre,
            ]),
        ];

        return Inertia::render('HojasRuta/Index', [
            'title' => 'Hoja de Ruta',
            'hojas' => $hojas,
            'filters' => $request->only(['search', 'estado', 'equipo', 'chofer', 'grupo']),
            'catalogos' => $catalogos,
            'filtros' => $this->filtrosHojas($entidadId, $inicioMes, $finMes),
            'fechaOperaciones' => $fechaOperaciones,
        ]);
    }

    /**
     * Opciones para los filtros del grid de hojas de ruta: SOLO tractivos,
     * choferes y grupos que aparecen en hojas de ruta del mes de operaciones.
     * Incluye las combinaciones reales para que los filtros sean dependientes
     * entre sí (mismo patrón que las cartas de porte).
     */
    private function filtrosHojas(?int $entidadId, string $inicioMes, string $finMes): array
    {
        $base = HojasRuta::query()
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('tractivo', fn ($t) => $t->whereIn('id_entidad', $this->entidadesPermitidas())))
            ->where(fn ($q) => $q
                ->whereNull('fecha_cierre')
                ->orWhereBetween('fecha_cierre', [$inicioMes, $finMes]));

        $tractivoIds = (clone $base)->whereNotNull('id_tractivo')->distinct()->pluck('id_tractivo');
        $grupoIds = (clone $base)->whereNotNull('id_grupo')->distinct()->pluck('id_grupo');
        $choferIds = (clone $base)->whereNotNull('id_chofer')->distinct()->pluck('id_chofer')
            ->merge((clone $base)->whereNotNull('id_chofer2')->distinct()->pluck('id_chofer2'))
            ->unique();

        return [
            'tractivos' => Tractivo::select('id', 'codigo')->whereIn('id', $tractivoIds)->orderBy('codigo')->get(),
            'grupos' => Grupo::select('id', 'nombre')->whereIn('id', $grupoIds)->orderBy('nombre')->get(),
            'choferes' => Bolsa::select('id', 'nombre', 'apellidos')->whereIn('id', $choferIds)->orderBy('nombre')->get(),
            // Combinaciones reales del mes para filtros encadenados
            'combinaciones' => (clone $base)
                ->select('id_tractivo', 'id_chofer', 'id_chofer2', 'id_grupo')
                ->get()
                ->map(fn ($h) => [
                    'tractivo' => $h->id_tractivo,
                    'chofer' => $h->id_chofer,
                    'chofer2' => $h->id_chofer2,
                    'grupo' => $h->id_grupo,
                ]),
        ];
    }

    public function store(Request $request)
    {
        $datos = $this->validarApertura($request);

        $this->autorizarTractivo($datos['id_tractivo'] ?? null);

        $this->service->abrir($datos, $request->user()->id);

        return back()->with('success', 'Hoja de Ruta abierta correctamente.');
    }

    public function update(Request $request, int $hoja)
    {
        $hojaModel = HojasRuta::findOrFail($hoja);
        $this->autorizarEntidad($hojaModel->id_entidad);

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

        $datos = $this->validarModificacion($request, $hoja);
        $this->autorizarTractivo($datos['id_tractivo'] ?? null);
        $this->service->modificar($hoja, $datos, $request->user()->id);

        return back()->with('success', 'Hoja de Ruta actualizada correctamente.');
    }

    public function destroy(Request $request, int $hoja)
    {
        $hojaModel = HojasRuta::findOrFail($hoja);
        $this->autorizarEntidad($hojaModel->id_entidad);

        if ($request->input('operacion') === 'cancelar') {
            try {
                $this->service->cancelar($hoja, $request->user()->id, session('fecha_operaciones'));
            } catch (HttpException $e) {
                throw ValidationException::withMessages(['general' => $e->getMessage()]);
            }

            return back()->with('success', 'Hoja de Ruta cancelada.');
        }

        try {
            $this->service->eliminar($hoja);
        } catch (HttpException $e) {
            throw ValidationException::withMessages(['general' => $e->getMessage()]);
        }

        return back()->with('success', 'Hoja de Ruta eliminada correctamente.');
    }

    /**
     * Autoriza operar sobre un tractivo: debe pertenecer a una entidad permitida.
     */
    private function autorizarTractivo(?int $idTractivo): void
    {
        if (! $idTractivo) {
            return;
        }

        $this->autorizarEntidad(Tractivo::find($idTractivo)?->id_entidad, 'No tiene permiso para operar con este tractivo.');
    }

    private function validarApertura(Request $request): array
    {
        return $request->validate([
            'numero' => ['required', 'string', 'max:50', $this->folioUnicoEnMes($request)],
            'fecha_emision' => ['required', 'date'],
            'hora_emision' => ['nullable', 'string', 'max:15'],
            'id_hr_anterior' => ['nullable', 'exists:hojas_ruta,id'],
            'id_tractivo' => ['required', 'exists:tractivos,id'],
            'id_arrastre' => ['nullable', 'exists:tractivos,id'],
            'id_chofer' => ['required', 'exists:bolsa,id'],
            'id_chofer2' => ['nullable', 'exists:bolsa,id'],
            'id_parqueo' => ['required', 'exists:lugares,id'],
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
                'numero_nueva' => ['required', 'string', 'max:50', $this->folioUnicoEnMes($request, 'numero_nueva')],
                'id_arrastre' => ['nullable', 'exists:tractivos,id'],
                'id_chofer' => ['nullable', 'exists:bolsa,id'],
                'id_parqueo' => ['nullable', 'exists:lugares,id'],
                'kms_disponible' => ['nullable', 'numeric', 'min:0'],
                'kms_disponibles_adicionales' => ['nullable', 'numeric', 'min:0'],
            ];
        }

        return $request->validate($reglas);
    }

    private function validarModificacion(Request $request, int $hoja): array
    {
        return $request->validate([
            'numero' => ['required', 'string', 'max:50', $this->folioUnicoEnMes($request, 'numero', $hoja)],
            'fecha_emision' => ['required', 'date'],
            'hora_emision' => ['nullable', 'string', 'max:15'],
            'fecha_cierre' => ['nullable', 'date'],
            'hora_cierre' => ['nullable', 'string', 'max:15'],
            'id_tractivo' => ['required', 'exists:tractivos,id'],
            'id_arrastre' => ['nullable', 'exists:tractivos,id'],
            'id_chofer' => ['required', 'exists:bolsa,id'],
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

    /**
     * Regla que impide duplicar el folio dentro del mismo mes y entidad.
     * Replica validar_folio del legacy (nrohr único por mes y unidad).
     */
    private function folioUnicoEnMes(Request $request, string $campo = 'numero', ?int $excluirId = null): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($request, $campo, $excluirId): void {
            $folio = trim((string) $value);
            if ($folio === '') {
                return;
            }

            $fecha = Carbon::parse($request->input('fecha_emision'));
            $entidadId = session('entidad_activa_id');

            $existe = HojasRuta::where('numero', $folio)
                ->whereYear('fecha_emision', $fecha->year)
                ->whereMonth('fecha_emision', $fecha->month)
                ->where('cancelada', false)
                ->whereNull('deleted_at')
                ->when($excluirId, fn ($q) => $q->where('id', '!=', $excluirId))
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->exists();

            if ($existe) {
                $fail("El folio {$folio} ya existe para {$fecha->toDateString()} en esta entidad.");
            }
        };
    }
}
