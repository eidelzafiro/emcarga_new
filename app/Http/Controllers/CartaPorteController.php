<?php

namespace App\Http\Controllers;

use App\Models\Bolsa;
use App\Models\Buque;
use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Distancia;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\Lugare;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\SolicitudesServicio;
use App\Models\TipoCarga;
use App\Models\Tractivo;
use App\Http\Controllers\Traits\EntidadScoping;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartaPorteController extends Controller
{
    use EntidadScoping;

    public function index(Request $request)
    {
        
        $this->authorize('viewAny', \App\Models\CartaPorte::class);
        $entidadId = session('entidad_activa_id');

        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $inicioMes = Carbon::parse($fechaOperaciones)->startOfMonth()->toDateString();
        $finMes = Carbon::parse($fechaOperaciones)->endOfMonth()->toDateString();

        // Relaciones del grid: cliente, origen, destino, choferes, tractivo+arrastre,
        // hoja de ruta (nrohr/fcierre) y la unidad vía la HR. Los aforos y facturas
        // alimentan las estrellas de recepción/aforo/factura.
        $cartas = CartaPorte::with([
            'hojaRuta:id,numero,fecha_cierre,id_entidad',
            'hojaRuta.entidad:id,nombre,abreviatura',
            'cliente',
            'lugarOrigen',
            'lugarDestino',
            'chofer',
            'chofer2',
            'tractivo',
            'arrastre',
            'solicitud:id,numero,id_lugar_origen,id_lugar_destino',
            'userCancelacion:id,name',
            'aforos:id,id_carta_porte,id_factura',
        ])
            ->withExists('aforos')
            ->withExists('facturas')
            // Entidad activa por la hoja de ruta de la carta
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $this->entidadesPermitidas())))
            // Emitidas dentro del mes de operaciones
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q2) => $q2
                ->where('numero', 'like', "%{$s}%")
                ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('chofer', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('chofer', fn ($c) => $c->where('apellidos', 'like', "%{$s}%"))
                ->orWhereHas('tractivo', fn ($c) => $c->where('codigo', 'like', "%{$s}%"))))
            // Equipo/choferes se derivan de la HR; cliente de la solicitud (Fase 4d)
            ->when($request->equipo, fn ($q, $v) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_tractivo', $v)))
            ->when($request->chofer, fn ($q, $v) => $q->whereHas('hojaRuta', fn ($h) => $h->where(fn ($h2) => $h2->where('id_chofer', $v)->orWhere('id_chofer2', $v))))
            ->when($request->cliente, fn ($q, $v) => $q->whereHas('solicitud', fn ($s) => $s->where('id_cliente', $v)))
            ->orderByDesc('fecha_emision')
            ->paginate(20);

        $hoy = now()->toDateString();

        $catalogos = $this->catalogos($entidadId, $hoy, $inicioMes, $finMes, $request);
        $filtros = $this->filtrosCartas($entidadId, $inicioMes, $finMes);

        // Si llega ?editar=<id> (desde una Hoja de Ruta) se abre el diálogo de
        // edición con la carta completa cargada, esté o no en la página actual.
        $cartaEditar = null;
        if ($request->filled('editar')) {
            $cartaEditar = CartaPorte::with([
                'hojaRuta:id,numero,fecha_cierre,id_entidad',
                'hojaRuta.entidad:id,nombre,abreviatura',
                'cliente',
                'lugarOrigen',
                'lugarDestino',
                'chofer',
                'chofer2',
                'tractivo',
                'arrastre',
                'solicitud:id,numero,id_lugar_origen,id_lugar_destino',
            ])
                ->withExists('aforos')
                ->withExists('facturas')
                ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $this->entidadesPermitidas())))
                ->find($request->integer('editar'));
        }

        return Inertia::render('CartaPorte/Index', [
            'title' => 'Carta de Porte',
            'cartas' => $cartas,
            'filters' => $request->only(['search', 'equipo', 'chofer', 'cliente']),
            'catalogos' => $catalogos,
            'filtros' => $filtros,
            'cartaEditar' => $cartaEditar,
            'fechaOperaciones' => $fechaOperaciones,
        ]);
    }

    /**
     * Opciones para los filtros del grid: SOLO clientes, choferes y equipos
     * que tienen carta de porte en el mes de operaciones. Además devuelve las
     * combinaciones reales (cliente, chofer, chofer2, tractivo) para que los
     * filtros sean dependientes entre sí.
     */
    private function filtrosCartas(?int $entidadId, string $inicioMes, string $finMes): array
    {
        $base = CartaPorte::query()
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $this->entidadesPermitidas())))
            ->whereBetween('fecha_emision', [$inicioMes, $finMes]);

        // Cliente desde la solicitud; equipo/choferes desde la hoja de ruta (Fase 4d)
        $clienteIds = (clone $base)->whereHas('solicitud')->with('solicitud:id,id_cliente')->get()
            ->map(fn ($c) => $c->solicitud?->id_cliente)->filter()->unique();
        $tractivoIds = (clone $base)->whereHas('hojaRuta')->with('hojaRuta:id,id_tractivo')->get()
            ->map(fn ($c) => $c->hojaRuta?->id_tractivo)->filter()->unique();
        $choferIds = (clone $base)->whereHas('hojaRuta')->with('hojaRuta:id,id_chofer,id_chofer2')->get()
            ->flatMap(fn ($c) => [$c->hojaRuta?->id_chofer, $c->hojaRuta?->id_chofer2])->filter()->unique();

        return [
            'clientes' => Cliente::select('id', 'nombre')->whereIn('id', $clienteIds)->orderBy('nombre')->get(),
            'tractivos' => Tractivo::select('id', 'codigo')->whereIn('id', $tractivoIds)->orderBy('codigo')->get(),
            'choferes' => Bolsa::select('id', 'nombre', 'apellidos')->whereIn('id', $choferIds)->orderBy('nombre')->get(),
            // Combinaciones reales del mes para filtros encadenados (Fase 4d)
            'combinaciones' => (clone $base)
                ->with(['hojaRuta:id,id_tractivo,id_arrastre,id_chofer,id_chofer2', 'solicitud:id,id_cliente'])
                ->get()
                ->map(fn ($c) => [
                    'cliente' => $c->solicitud?->id_cliente,
                    'chofer' => $c->hojaRuta?->id_chofer,
                    'chofer2' => $c->hojaRuta?->id_chofer2,
                    'tractivo' => $c->hojaRuta?->id_tractivo,
                ]),
        ];
    }

    /**
     * Catálogos compartidos para el grid y el formulario de emisión.
     */
    private function catalogos(?int $entidadId, string $hoy, string $inicioMes, string $finMes, Request $request): array
    {
        return [
            // Hojas de ruta emitidas en el mes, para el combo de la emisión y el filtro
            'hojasRuta' => HojasRuta::select('id', 'numero', 'fecha_emision', 'fecha_cierre', 'id_tractivo', 'id_arrastre', 'id_chofer', 'id_chofer2', 'id_entidad')
                ->with(['tractivo', 'arrastre', 'chofer', 'chofer2'])
                ->selectRaw('COALESCE(fecha_cierre, fecha_emision) as ref_fecha')
                ->where(fn ($q) => $q->whereNull('fecha_cierre')->orWhereBetween('fecha_cierre', [$inicioMes, $finMes]))
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderByDesc('ref_fecha')
                ->limit(200)
                ->get()
                ->map(fn ($hr) => [
                    'id' => $hr->id,
                    'numero' => $hr->numero,
                    'fecha_cierre' => $hr->fecha_cierre,
                    'id_chofer' => $hr->id_chofer,
                    'id_chofer2' => $hr->id_chofer2,
                    'id_tractivo' => $hr->id_tractivo,
                    'id_arrastre' => $hr->id_arrastre,
                    'tractivo_codigo' => $hr->tractivo?->codigo,
                    'arrastre_codigo' => $hr->arrastre?->codigo,
                    'chofer_nombre' => $hr->chofer ? trim($hr->chofer->nombre.' '.$hr->chofer->apellidos) : null,
                    'chofer2_nombre' => $hr->chofer2 ? trim($hr->chofer2->nombre.' '.$hr->chofer2->apellidos) : null,
                ]),
            'clientes' => Cliente::select('id', 'codigo', 'nombre')
                ->where('activo', true)
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderBy('nombre')
                ->get(),
            'solicitudes' => SolicitudesServicio::select('id', 'numero', 'id_cliente', 'id_lugar_origen', 'id_lugar_destino', 'id_producto', 'id_producto2', 'id_tipo_carga', 'id_tipo_carga2', 'id_moneda')
                ->with(['cliente:id,nombre', 'lugarOrigen:id,nombre', 'lugarDestino:id,nombre'])
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderByDesc('fecha_solicitud')
                ->limit(300)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'numero' => $s->numero,
                    'cliente_nombre' => $s->cliente?->nombre,
                    'lugar_origen_nombre' => $s->lugarOrigen?->nombre,
                    'lugar_destino_nombre' => $s->lugarDestino?->nombre,
                    'id_cliente' => $s->id_cliente,
                    'id_lugar_origen' => $s->id_lugar_origen,
                    'id_lugar_destino' => $s->id_lugar_destino,
                    'id_producto' => $s->id_producto,
                    'id_producto2' => $s->id_producto2,
                    'id_tipo_carga' => $s->id_tipo_carga,
                    'id_tipo_carga2' => $s->id_tipo_carga2,
                    'id_moneda' => $s->id_moneda,
                ]),
            'lugares' => Lugare::select('id', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'productos' => Producto::select('id', 'codigo', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'tiposCargas' => TipoCarga::select('id', 'codigo', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'monedas' => Moneda::select('id', 'codigo', 'nombre', 'simbolo')->where('activo', true)->orderBy('nombre')->get(),
            'buques' => Buque::select('id', 'codigo', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'tractivos' => Tractivo::with('grupo:id,nombre')
                ->select('id', 'codigo', 'id_entidad', 'marca', 'modelo', 'placa', 'id_grupo', 'kms_disp')
                ->whereNull('fecha_baja')
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderBy('codigo')
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'codigo' => $t->codigo,
                    'tipo' => $t->grupo?->nombre,
                    'marca' => $t->marca,
                    'placa' => $t->placa,
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
                ]),
            'choferes' => Bolsa::select('id', 'nombre', 'apellidos', 'ci', 'categorias_licencia')
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
                ]),
        ];
    }

    public function store(Request $request)
    {
        
        $this->authorize('create', \App\Models\CartaPorte::class);
        $validated = $this->validar($request);

        // La hoja de ruta / solicitud usadas deben pertenecer a la entidad permitida.
        if (! empty($validated['id_hoja_ruta'])) {
            $this->autorizarEntidad(HojasRuta::find($validated['id_hoja_ruta'])?->id_entidad);
        }
        if (! empty($validated['id_solicitud'])) {
            $this->autorizarEntidad(SolicitudesServicio::find($validated['id_solicitud'])?->id_entidad);
        }

        $validated['id_user'] = auth()->id();
        $validated['estado'] = 'emitida';
        $validated['fecha_emision'] = $validated['fecha_emision'] ?? now()->toDateString();
        $validated['fecha_parte'] = $validated['fecha_parte'] ?? $validated['fecha_emision'];
        $validated['imprimir'] = $request->boolean('imprimir');

        if (empty($validated['toneladas'])) {
            $validated['toneladas'] = (float) ($validated['peso1'] ?? 0) + (float) ($validated['peso2'] ?? 0);
        }

        // Fase 4d: equipo/choferes se derivan de la HR; los fletes viven en
        // `aforos`. La carta persiste folio, HR, fechas, pesos y distancia.
        // Los datos generales (cliente/lugares/productos/tipos/moneda) se
        // sincronizan con la solicitud (se crea si la CP va "desde cero").

        $validated['id_solicitud'] = $this->sincronizarSolicitud($validated);

        $carta = CartaPorte::create($validated);

        return back()->with('success', "Carta de porte {$carta->numero} emitida.");
    }

    public function update(Request $request, CartaPorte $carta)
    {
        
        $this->authorize('update', $carta);
        $this->autorizarEntidadCarta($carta);

        if ($carta->cancelada) {
            return back()->with('error', 'No es posible modificar una carta de porte cancelada.');
        }

        $validated = $this->validar($request, $carta);

        if (empty($validated['toneladas'])) {
            $validated['toneladas'] = (float) ($validated['peso1'] ?? 0) + (float) ($validated['peso2'] ?? 0);
        }

        // Sincroniza los datos generales con la solicitud (la actualiza o la crea).
        $validated['id_solicitud'] = $this->sincronizarSolicitud($validated);

        $carta->update($validated);

        $this->recalcularSolicitud($carta);

        return back()->with('success', "Carta de porte {$carta->numero} actualizada.");
    }

    public function destroy(Request $request, CartaPorte $carta)
    {
        
        $this->authorize('delete', $carta);
        $this->autorizarEntidadCarta($carta);

        if ($request->input('operacion') === 'cancelar') {
            if ($carta->cancelada) {
                return back()->with('error', 'La carta de porte ya estaba cancelada.');
            }

            if ($carta->aforos()->exists() || $carta->facturas()->exists()) {
                return back()->with('error', 'No es posible cancelar una carta de porte con aforos o facturas.');
            }

            $carta->update([
                'cancelada' => true,
                'estado' => 'cancelada',
                'notas' => $request->input('notas') ?? $carta->notas,
                'id_user_cancelacion' => $request->user()->id,
                'fecha_cancelacion' => now(),
            ]);

            $this->recalcularSolicitud($carta);

            return back()->with('success', "Carta de porte {$carta->numero} cancelada.");
        }

        $idSolicitud = $carta->id_solicitud;
        $numero = $carta->numero;
        $carta->delete();

        $this->recalcularSolicitud($idSolicitud);

        return back()->with('success', "Carta de porte {$numero} eliminada.");
    }

    /**
     * Autoriza el acceso a una carta de porte. La entidad de la CP se deriva de
     * su hoja de ruta; si no tiene, de su solicitud de servicio.
     */
    private function autorizarEntidadCarta(CartaPorte $carta): void
    {
        $idEntidad = $carta->hojaRuta?->id_entidad ?? $carta->solicitud?->id_entidad;

        $this->autorizarEntidad($idEntidad, 'No tiene permiso para acceder a esta carta de porte.');
    }

    /**
     * Recalcula el estado de la solicitud de servicio asociada a una carta de
     * porte (al editar, cancelar o eliminar). Acepta la carta o su id_solicitud.
     */
    private function recalcularSolicitud(CartaPorte|int $carta): void
    {
        $idSolicitud = $carta instanceof CartaPorte ? $carta->id_solicitud : $carta;

        if ($idSolicitud) {
            SolicitudesServicio::find($idSolicitud)?->recalcularEstado();
        }
    }

    /**
     * Valida un folio manual en la emisión (único por mes y entidad).
     */
    public function validarFolio(Request $request)
    {
        $validated = $request->validate([
            'numero' => ['required', 'string', 'max:20'],
            'fecha_emision' => ['required', 'date'],
            'excluir' => ['nullable', 'integer'],
        ]);

        $fecha = Carbon::parse($validated['fecha_emision']);

        $existe = CartaPorte::where('numero', $validated['numero'])
            ->whereYear('fecha_emision', $fecha->year)
            ->whereMonth('fecha_emision', $fecha->month)
            ->where('cancelada', false)
            ->whereNull('deleted_at')
            ->when($validated['excluir'] ?? null, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists();

        return response()->json(['disponible' => ! $existe]);
    }

    /**
     * Devuelve la distancia y tarifa para un par origen-destino.
     */
    public function obtenerDistancia(Request $request)
    {
        $validated = $request->validate([
            'id_lugar_origen' => ['required', 'exists:lugares,id'],
            'id_lugar_destino' => ['required', 'exists:lugares,id'],
        ]);

        $distancia = Distancia::where('id_lugar_origen', $validated['id_lugar_origen'])
            ->where('id_lugar_destino', $validated['id_lugar_destino'])
            ->value('distancia_km');

        return response()->json(['distancia' => $distancia]);
    }

    /**
     * Recepciona la carta marcando la fecha de recepción.
     */
    public function recepcionar(Request $request, CartaPorte $carta)
    {
        $this->autorizarEntidadCarta($carta);

        if ($carta->cancelada) {
            return back()->with('error', 'No es posible recepcionar una carta de porte cancelada.');
        }

        $carta->update([
            'fecha_recepcion' => $request->input('fecha_recepcion') ?? now()->toDateString(),
            'id_user_recepcion' => auth()->id(),
            'estado' => 'recepcionada',
        ]);

        $this->recalcularSolicitud($carta);

        return back()->with('success', "Carta de porte {$carta->numero} recepcionada.");
    }

    /**
     * Sincroniza los datos generales de la carta de porte con su solicitud.
     *
     * - Si la CP tiene `id_solicitud`: actualiza la solicitud con los datos
     *   generales editables (cliente, lugares, productos, tipos, moneda, pesos).
     * - Si no la tiene: crea una solicitud nueva con esos datos y la devuelve
     *   para asignarla a la carta. Así, una CP emitida "desde cero" genera su
     *   solicitud automáticamente.
     *
     * @return int|null id de la solicitud vinculada
     */
    private function sincronizarSolicitud(array $v): ?int
    {
        $idSolicitud = $v['id_solicitud'] ?? null;
        $solicitud = $idSolicitud ? SolicitudesServicio::find($idSolicitud) : null;

        if ($solicitud) {
            $this->autorizarEntidad($solicitud->id_entidad);
        }

        $datos = [
            'id_cliente' => $v['id_cliente'] ?? null,
            'id_lugar_origen' => $v['id_lugar_origen'] ?? null,
            'id_lugar_destino' => $v['id_lugar_destino'] ?? null,
            'id_producto' => $v['id_producto'] ?? null,
            'id_producto2' => $v['id_producto2'] ?? null,
            'id_tipo_carga' => $v['id_tipo_carga'] ?? null,
            'id_tipo_carga2' => $v['id_tipo_carga2'] ?? null,
            'id_moneda' => $v['id_moneda'] ?? null,
            'peso1' => $v['peso1'] ?? 0,
            'peso2' => $v['peso2'] ?? 0,
            'distancia' => $v['distancia'] ?? null,
        ];

        // Solo los campos que llegan en la petición (para no pisar datos al editar).
        $datos = array_filter($datos, fn ($valor, $clave) => array_key_exists($clave, $v), ARRAY_FILTER_USE_BOTH);

        if ($solicitud) {
            if ($datos) {
                $solicitud->update($datos);
            }

            return $solicitud->id;
        }

        // No hay solicitud vinculada: se crea una nueva con los datos generales.
        $fechaEmision = $v['fecha_emision'] ?? now()->toDateString();
        $nueva = SolicitudesServicio::create(array_merge($datos, [
            'numero' => $this->siguienteNumeroSolicitud($fechaEmision),
            'id_entidad' => auth()->user()?->entidad_activa_id ?? session('entidad_activa_id') ?: null,
            'id_user' => auth()->id(),
            'fecha_solicitud' => $fechaEmision,
            'estado' => 'pendiente',
        ]));

        return $nueva->id;
    }

    private function siguienteNumeroSolicitud(string $fecha): string
    {
        $anio = substr($fecha, 0, 4);
        $base = 'SOL-'.$anio.'-';
        $ultimo = SolicitudesServicio::where('numero', 'like', $base.'%')
            ->orderBy('numero', 'desc')
            ->value('numero');
        $sec = $ultimo ? ((int) substr($ultimo, strlen($base))) + 1 : 1;

        return $base.str_pad((string) $sec, 5, '0', STR_PAD_LEFT);
    }

    private function validar(Request $request, ?CartaPorte $carta = null): array
    {
        $mes = fn () => $request->input('fecha_emision') ? Carbon::parse($request->input('fecha_emision'))->format('Y-m') : now()->format('Y-m');

        // Las fechas deben quedar dentro del mes de operaciones en curso.
        $mesOperaciones = function (?string $fecha = null): \Closure {
            return function (string $attribute, mixed $value, \Closure $fail) use ($fecha): void {
                if (! $value) {
                    return;
                }
                $referencia = $fecha ?? session('fecha_operaciones') ?? now()->toDateString();
                $fechaVal = Carbon::parse($value);
                $ref = Carbon::parse($referencia);
                if ($fechaVal->format('Y-m') !== $ref->format('Y-m')) {
                    $fail('La fecha debe estar dentro del mes de operaciones ('.ucfirst($ref->translatedFormat('F Y')).').');
                }
            };
        };

        return $request->validate([
            'numero' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail) use ($carta): void {
                $existe = CartaPorte::where('numero', trim((string) $value))
                    ->where('cancelada', false)
                    ->whereNull('deleted_at')
                    ->when($carta, fn ($q) => $q->where('id', '!=', $carta->id))
                    ->exists();
                if ($existe) {
                    $fail('El folio ya está registrado. Verifique antes de emitirlo.');
                }
            }],
            'fecha_emision' => ['nullable', 'date', $mesOperaciones()],
            'fecha_parte' => ['nullable', 'date', $mesOperaciones()],
            'fecha_recepcion' => ['nullable', 'date'],
            'id_hoja_ruta' => ['nullable', 'exists:hojas_ruta,id'],
            'id_solicitud' => ['nullable', 'exists:solicitudes_servicio,id'],
            'toneladas' => ['nullable', 'numeric', 'min:0'],
            'peso1' => ['nullable', 'numeric', 'min:0'],
            'peso2' => ['nullable', 'numeric', 'min:0'],
            'distancia' => ['nullable', 'integer', 'min:0'],
            'conduce' => ['nullable', 'string', 'max:150'],
            'notas' => ['nullable', 'string', 'max:150'],
            'imprimir' => ['sometimes', 'boolean'],
            'estado' => ['nullable', 'string', 'in:emitida,recepcionada,facturada,cancelada'],

            // Datos generales de la solicitud (se crea o actualiza al guardar)
            'id_cliente' => ['nullable', 'exists:clientes,id'],
            'id_lugar_origen' => ['nullable', 'exists:lugares,id'],
            'id_lugar_destino' => ['nullable', 'exists:lugares,id'],
            'id_producto' => ['nullable', 'exists:productos,id'],
            'id_producto2' => ['nullable', 'exists:productos,id'],
            'id_tipo_carga' => ['nullable', 'exists:tipos_cargas,id'],
            'id_tipo_carga2' => ['nullable', 'exists:tipos_cargas,id'],
            'id_moneda' => ['nullable', 'exists:monedas,id'],
        ]);
    }
}
