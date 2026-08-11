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
use App\Models\TipoCarga;
use App\Models\Tractivo;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CartaPorteController extends Controller
{
    public function index(Request $request)
    {
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
            'cliente:id,nombre',
            'lugarOrigen:id,nombre',
            'lugarDestino:id,nombre',
            'chofer:id,nombre,apellidos',
            'chofer2:id,nombre,apellidos',
            'tractivo:id,codigo',
            'arrastre:id,codigo',
            'solicitud:id,numero,id_lugar_origen,id_lugar_destino',
            'userCancelacion:id,name',
        ])
            ->withExists('aforos')
            ->withExists('facturas')
            // Entidad activa por la hoja de ruta de la carta
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            // Emitidas dentro del mes de operaciones
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q2) => $q2
                ->where('numero', 'like', "%{$s}%")
                ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('chofer', fn ($c) => $c->where('nombre', 'like', "%{$s}%"))
                ->orWhereHas('chofer', fn ($c) => $c->where('apellidos', 'like', "%{$s}%"))
                ->orWhereHas('tractivo', fn ($c) => $c->where('codigo', 'like', "%{$s}%"))))
            ->when($request->equipo, fn ($q, $v) => $q->where('id_tractivo', $v))
            ->when($request->chofer, fn ($q, $v) => $q->where(fn ($q2) => $q2->where('id_chofer', $v)->orWhere('id_chofer2', $v)))
            ->when($request->cliente, fn ($q, $v) => $q->where('id_cliente', $v))
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
                'cliente:id,nombre',
                'lugarOrigen:id,nombre',
                'lugarDestino:id,nombre',
                'chofer:id,nombre,apellidos',
                'chofer2:id,nombre,apellidos',
                'tractivo:id,codigo',
                'arrastre:id,codigo',
                'solicitud:id,numero,id_lugar_origen,id_lugar_destino',
            ])
                ->withExists('aforos')
                ->withExists('facturas')
                ->find($request->integer('editar'));
        }

        return Inertia::render('CartaPorte/Index', [
            'title' => 'Carta de Porte',
            'cartas' => $cartas,
            'filters' => $request->only(['search', 'equipo', 'chofer', 'cliente']),
            'catalogos' => $catalogos,
            'filtros' => $filtros,
            'cartaEditar' => $cartaEditar,
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
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta', fn ($h) => $h->where('id_entidad', $entidadId)))
            ->whereBetween('fecha_emision', [$inicioMes, $finMes]);

        $clienteIds = (clone $base)->whereNotNull('id_cliente')->distinct()->pluck('id_cliente');
        $tractivoIds = (clone $base)->whereNotNull('id_tractivo')->distinct()->pluck('id_tractivo');
        $choferIds = (clone $base)->whereNotNull('id_chofer')->distinct()->pluck('id_chofer')
            ->merge((clone $base)->whereNotNull('id_chofer2')->distinct()->pluck('id_chofer2'))
            ->unique();

        return [
            'clientes' => Cliente::select('id', 'nombre')->whereIn('id', $clienteIds)->orderBy('nombre')->get(),
            'tractivos' => Tractivo::select('id', 'codigo')->whereIn('id', $tractivoIds)->orderBy('codigo')->get(),
            'choferes' => Bolsa::select('id', 'nombre', 'apellidos')->whereIn('id', $choferIds)->orderBy('nombre')->get(),
            // Combinaciones reales del mes para filtros encadenados
            'combinaciones' => (clone $base)
                ->select('id_cliente', 'id_chofer', 'id_chofer2', 'id_tractivo')
                ->get()
                ->map(fn ($c) => [
                    'cliente' => $c->id_cliente,
                    'chofer' => $c->id_chofer,
                    'chofer2' => $c->id_chofer2,
                    'tractivo' => $c->id_tractivo,
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
            'hojasRuta' => HojasRuta::select('id', 'numero', 'fecha_emision', 'fecha_cierre', 'id_tractivo', 'id_arrastre', 'id_chofer', 'id_chofer2', 'id_entidad', 'id_cliente')
                ->with(['tractivo:id,codigo', 'arrastre:id,codigo', 'chofer:id,nombre,apellidos', 'chofer2:id,nombre,apellidos'])
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
                    'id_cliente' => $hr->id_cliente,
                ]),
            'clientes' => Cliente::select('id', 'codigo', 'nombre')
                ->where('activo', true)
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderBy('nombre')
                ->get(),
            'lugares' => Lugare::select('id', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'productos' => Producto::select('id', 'codigo', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'tiposCargas' => TipoCarga::select('id', 'codigo', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'monedas' => Moneda::select('id', 'codigo', 'nombre', 'simbolo')->where('activo', true)->orderBy('nombre')->get(),
            'buques' => Buque::select('id', 'codigo', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
            'turnos' => Turno::select('id', 'codigo', 'nombre')->where('activo', true)->orderBy('nombre')->get(),
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
        $validated = $this->validar($request);

        $validated['id_user'] = auth()->id();
        $validated['estado'] = 'emitida';
        $validated['fecha_emision'] = $validated['fecha_emision'] ?? now()->toDateString();
        $validated['fecha_parte'] = $validated['fecha_parte'] ?? $validated['fecha_emision'];
        $validated['imprimir'] = $request->boolean('imprimir');

        if (empty($validated['toneladas'])) {
            $validated['toneladas'] = (float) ($validated['peso1'] ?? 0) + (float) ($validated['peso2'] ?? 0);
        }

        if (isset($validated['kms1']) || isset($validated['kms2'])) {
            $kmsTotal = (float) ($validated['kms1'] ?? 0) + (float) ($validated['kms2'] ?? 0);
            if ($kmsTotal > 0) {
                $validated['distancia'] = $kmsTotal;
            }
        }

        if (isset($validated['id_hoja_ruta'])) {
            $hoja = HojasRuta::with('tractivo:id,id_entidad')->find($validated['id_hoja_ruta']);
            // Si no se especificaron, hereda equipo/choferes/cliente de la hoja
            $validated['id_tractivo'] = $validated['id_tractivo'] ?? $hoja?->id_tractivo;
            $validated['id_arrastre'] = $validated['id_arrastre'] ?? $hoja?->id_arrastre;
            $validated['id_chofer'] = $validated['id_chofer'] ?? $hoja?->id_chofer;
            $validated['id_chofer2'] = $validated['id_chofer2'] ?? $hoja?->id_chofer2;
            $validated['id_cliente'] = $validated['id_cliente'] ?? $hoja?->id_cliente;
        }

        if (isset($validated['id_lugar_origen'], $validated['id_lugar_destino']) && empty($validated['distancia'])) {
            $validated['distancia'] = Distancia::where('id_lugar_origen', $validated['id_lugar_origen'])
                ->where('id_lugar_destino', $validated['id_lugar_destino'])
                ->value('distancia_km');
        }

        if (isset($validated['distancia'], $validated['tarifa_km'])) {
            $validated['total_flete'] = round((float) $validated['distancia'] * (float) $validated['tarifa_km'], 2);
        }

        $carta = CartaPorte::create($validated);

        return back()->with('success', "Carta de porte {$carta->numero} emitida.");
    }

    public function update(Request $request, CartaPorte $carta)
    {
        if ($carta->cancelada) {
            return back()->with('error', 'No es posible modificar una carta de porte cancelada.');
        }

        $validated = $this->validar($request, $carta);

        if (isset($validated['kms1']) || isset($validated['kms2'])) {
            $kmsTotal = (float) ($validated['kms1'] ?? 0) + (float) ($validated['kms2'] ?? 0);
            if ($kmsTotal > 0) {
                $validated['distancia'] = $kmsTotal;
            }
        }

        if (isset($validated['id_lugar_origen'], $validated['id_lugar_destino'])) {
            $distancia = Distancia::where('id_lugar_origen', $validated['id_lugar_origen'])
                ->where('id_lugar_destino', $validated['id_lugar_destino'])
                ->value('distancia_km');
            if ($distancia) {
                $validated['distancia'] = $distancia;
            }
        }

        if (isset($validated['distancia'], $validated['tarifa_km']) && ! empty($validated['tarifa_km'])) {
            $validated['total_flete'] = round((float) $validated['distancia'] * (float) $validated['tarifa_km'], 2);
        }

        $carta->update($validated);

        return back()->with('success', "Carta de porte {$carta->numero} actualizada.");
    }

    public function destroy(Request $request, CartaPorte $carta)
    {
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

            return back()->with('success', "Carta de porte {$carta->numero} cancelada.");
        }

        $numero = $carta->numero;
        $carta->delete();

        return back()->with('success', "Carta de porte {$numero} eliminada.");
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
        if ($carta->cancelada) {
            return back()->with('error', 'No es posible recepcionar una carta de porte cancelada.');
        }

        $carta->update([
            'fecha_recepcion' => $request->input('fecha_recepcion') ?? now()->toDateString(),
            'id_user_recepcion' => auth()->id(),
            'estado' => 'recepcionada',
        ]);

        return back()->with("success", "Carta de porte {$carta->numero} recepcionada.");
    }

    private function validar(Request $request, ?CartaPorte $carta = null): array
    {
        $mes = fn () => $request->input('fecha_emision') ? Carbon::parse($request->input('fecha_emision'))->format('Y-m') : now()->format('Y-m');

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
            'fecha_emision' => ['nullable', 'date'],
            'fecha_parte' => ['nullable', 'date'],
            'fecha_recepcion' => ['nullable', 'date'],
            'id_hoja_ruta' => ['nullable', 'exists:hojas_ruta,id'],
            'id_solicitud' => ['nullable', 'exists:solicitudes_servicio,id'],
            'id_tractivo' => ['nullable', 'exists:tractivos,id'],
            'id_arrastre' => ['nullable', 'exists:tractivos,id'],
            'id_cliente' => ['nullable', 'exists:clientes,id'],
            'id_chofer' => ['nullable', 'exists:bolsa,id'],
            'id_chofer2' => ['nullable', 'exists:bolsa,id'],
            'id_lugar_origen' => ['nullable', 'exists:lugares,id'],
            'id_lugar_destino' => ['nullable', 'exists:lugares,id'],
            'id_producto' => ['nullable', 'exists:productos,id'],
            'id_producto2' => ['nullable', 'exists:productos,id'],
            'id_tipo_carga' => ['nullable', 'exists:tipos_cargas,id'],
            'id_tipo_carga2' => ['nullable', 'exists:tipos_cargas,id'],
            'id_buque' => ['nullable', 'exists:buques,id'],
            'id_turno' => ['nullable', 'exists:turnos,id'],
            'id_moneda' => ['nullable', 'exists:monedas,id'],
            'toneladas' => ['nullable', 'numeric', 'min:0'],
            'peso1' => ['nullable', 'numeric', 'min:0'],
            'peso2' => ['nullable', 'numeric', 'min:0'],
            'distancia' => ['nullable', 'integer', 'min:0'],
            'kms1' => ['nullable', 'numeric', 'min:0'],
            'kms2' => ['nullable', 'numeric', 'min:0'],
            'tarifa_km' => ['nullable', 'numeric', 'min:0'],
            'total_flete' => ['nullable', 'numeric', 'min:0'],
            'ingreso_mt' => ['nullable', 'numeric', 'min:0'],
            'flete_mt' => ['nullable', 'numeric', 'min:0'],
            'conduce' => ['nullable', 'string', 'max:150'],
            'notas' => ['nullable', 'string', 'max:150'],
            'imprimir' => ['sometimes', 'boolean'],
            'estado' => ['nullable', 'string', 'in:emitida,recepcionada,facturada,cancelada'],
        ]);
    }
}