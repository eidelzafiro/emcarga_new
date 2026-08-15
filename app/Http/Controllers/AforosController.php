<?php

namespace App\Http\Controllers;

use App\Models\Aforo;
use App\Models\Bolsa;
use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\Indicadore;
use App\Models\Lugare;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\TipoCarga;
use App\Models\Tractivo;
use App\Services\AforoCotizadorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AforosController extends Controller
{
    public function __construct(
        private readonly AforoCotizadorService $cotizador,
    ) {}

    /**
     * Grid de aforos con paridad legacy. Filtrado por unidad (entidad) y el mes
     * en curso según la fecha de operaciones de la sesión, más filtros de
     * cliente, chofer y equipo.
     */
    public function index(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');

        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = Carbon::parse($fechaOperaciones)->year;
        $mes = Carbon::parse($fechaOperaciones)->month;

        $query = Aforo::with([
            'cartaPorte:id,numero,id_hoja_ruta,id_solicitud,distancia',
            'cartaPorte.cliente',
            'cartaPorte.tractivo',
            'cartaPorte.hojaRuta:id,numero,id_entidad',
            'cartaPorte.lugarOrigen',
            'cartaPorte.lugarDestino',
            'factura:id,numero',
        ]);

        // Solo el mes en curso de operaciones
        $query->whereYear('fecha_parte', $anio)->whereMonth('fecha_parte', $mes);

        $query->when($request->search, function ($q, $s) {
            $q->where(function ($q2) use ($s) {
                $q2->whereHas('cartaPorte', fn ($q3) => $q3->where('numero', 'like', "%{$s}%"))
                    ->orWhereHas('cartaPorte.cliente', fn ($q3) => $q3->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('cartaPorte.tractivo', fn ($q3) => $q3->where('codigo', 'like', "%{$s}%"))
                    ->orWhereHas('cartaPorte.hojaRuta', fn ($q3) => $q3->where('numero', 'like', "%{$s}%"));
            });
        });

        // Cliente desde la solicitud; equipo/choferes desde la hoja de ruta (Fase 4d)
        $query->when($request->cliente, fn ($q, $v) => $q->whereHas('cartaPorte.solicitud', fn ($c) => $c->where('id_cliente', $v)));
        $query->when($request->equipo, fn ($q, $v) => $q->whereHas('cartaPorte.hojaRuta', fn ($c) => $c->where('id_tractivo', $v)));
        $query->when($request->chofer, fn ($q, $v) => $q->whereHas('cartaPorte.hojaRuta', fn ($c) => $c->where(fn ($c2) => $c2->where('id_chofer', $v)->orWhere('id_chofer2', $v))));

        $query->when($request->estado, function ($q, $v) {
            if ($v === 'pendiente') {
                $q->whereNull('id_factura')->whereNull('id_prefactura');
            } elseif ($v === 'facturado') {
                $q->whereNotNull('id_factura');
            } elseif ($v === 'prefacturado') {
                $q->whereNull('id_factura')->whereNotNull('id_prefactura');
            }
        });

        if ($entidadId) {
            $ids = collect(Entidad::subEntidadesIds($entidadId))
                ->push($entidadId)
                ->unique()
                ->values()
                ->all();
            $query->whereHas('cartaPorte.hojaRuta.tractivo', fn ($q) => $q->whereIn('id_entidad', $ids));
        }

        // Opciones para los filtros: solo de la entidad actual en el mes en curso
        $base = Aforo::query()->with([
            'cartaPorte:id,id_hoja_ruta,id_solicitud',
            'cartaPorte.hojaRuta:id,id_tractivo,id_chofer,id_chofer2',
            'cartaPorte.solicitud:id,id_cliente',
        ])
            ->whereYear('fecha_parte', $anio)->whereMonth('fecha_parte', $mes);
        if ($entidadId) {
            $ids = collect(Entidad::subEntidadesIds($entidadId))->push($entidadId)->unique()->values()->all();
            $base->whereHas('cartaPorte.hojaRuta.tractivo', fn ($q) => $q->whereIn('id_entidad', $ids));
        }

        $cartasDelMes = (clone $base)->get()->pluck('cartaPorte')->filter();

        $clientesIds = $cartasDelMes->map(fn ($c) => $c->solicitud?->id_cliente)->filter()->unique();
        $tractivosIds = $cartasDelMes->map(fn ($c) => $c->hojaRuta?->id_tractivo)->filter()->unique();
        $choferesIds = $cartasDelMes->flatMap(fn ($c) => [$c->hojaRuta?->id_chofer, $c->hojaRuta?->id_chofer2])->filter()->unique();

        $filtros = [
            'clientes' => Cliente::select('id', 'nombre')->whereIn('id', $clientesIds)->orderBy('nombre')->get(),
            'tractivos' => Tractivo::select('id', 'codigo')->whereIn('id', $tractivosIds)->orderBy('codigo')->get(),
            'choferes' => Bolsa::select('id', 'nombre', 'apellidos')->whereIn('id', $choferesIds)->orderBy('nombre')->get(),
        ];

        $aforos = $query->orderByDesc('fecha_parte')->orderByDesc('id')->paginate(20);

        return Inertia::render('Aforos/Index', [
            'title' => 'Aforos',
            'aforos' => $aforos,
            'filters' => $request->only(['search', 'estado', 'cliente', 'chofer', 'equipo']),
            'filtros' => $filtros,
            'fechaOperaciones' => $fechaOperaciones,
        ]);
    }

    public function show(Aforo $aforo)
    {
        $aforo->load([
            'cartaPorte.cliente',
            'cartaPorte.tractivo',
            'cartaPorte.chofer',
            'cartaPorte.chofer2',
            'cartaPorte.lugarOrigen',
            'cartaPorte.lugarDestino',
            'cartaPorte.producto',
            'cartaPorte.tipoCarga',
            'cartaPorte.hojaRuta:id,numero',
            'factura:id,numero,estado',
            'prefactura:id,numero,estado',
            'tasa:id,nombre,tasa',
            'user:id,name',
        ]);

        return Inertia::render('Aforos/Show', [
            'title' => 'Aforo '.$aforo->cartaPorte?->numero,
            'aforo' => $aforo,
        ]);
    }

    /**
     * Formulario de aforo. NO crea la carta de porte: selecciona una CP ya girada,
     * no aforada y del mes de operaciones (paridad legacy `cartaporte/obtener_aforo`).
     */
    public function create(Request $request)
    {
        $data = $this->datosFormulario($request);

        return Inertia::render('Aforos/Form', $data);
    }

    /**
     * Formulario de edición de un aforo existente. Carga todos los valores
     * guardados (desglose, salario, indicadores, tasa seleccionada) para
     * permitir corregir el aforo y los datos generales de la CP.
     */
    public function edit(Aforo $aforo)
    {
        abort_if($aforo->id_factura, 403, 'No es posible editar una carta de porte ya facturada.');

        $aforo->load('cartaPorte.tractivo');
        $request = request();

        // Cargar la CP completa del aforo para poder editar sus datos generales
        $carta = $aforo->cartaPorte;
        $carta->load([
            'hojaRuta:id,numero,fecha_cierre,id_entidad',
            'solicitud:id,numero,id_lugar_origen,id_lugar_destino,id_moneda,id_cliente,id_producto,id_tipo_carga',
            'cliente',
            'tractivo',
            'arrastre',
            'chofer',
            'chofer2',
            'lugarOrigen',
            'lugarDestino',
            'producto',
            'tipoCarga',
        ]);

        $data = $this->datosFormulario($request);
        $data['cartaPreseleccionada'] = $carta;

        // Líneas de tarifa desde el desglose guardado
        $lineas = collect(range(1, 5))->map(fn ($n) => [
            'id_tipo_carga' => $aforo->{"id_tipo_carga_{$n}"} ?? null,
            'peso_cobrar' => (float) $aforo->{"peso_cobrar_{$n}"},
            'distancia' => (float) $aforo->{"distancia_{$n}"},
            'descuento' => (float) $aforo->{"desc_{$n}"},
            'tarifa_mt' => (float) $aforo->{"tarifa_mt_{$n}"},
            'flete_mt' => (float) $aforo->{"flete_mt_{$n}"},
            'flete_mlc' => (float) $aforo->{"flete_mlc_{$n}"},
        ])->values();

        // Indicadores filas 3-5 desde la tabla `indicadores`
        $ind = $aforo->indicadores;
        $indFilas = collect(range(1, 5))->map(function ($n) use ($aforo, $ind) {
            if ($n <= 2) {
                return [
                    'tn_pos' => (float) $aforo->{"tn_pos_{$n}"},
                    'tn_real' => (float) $aforo->{"tn_real_{$n}"},
                    'km_carga' => (float) $aforo->{"km_carga_{$n}"},
                    'km_vacio' => (float) $aforo->{"km_vacio_{$n}"},
                    'km_total' => (float) $aforo->{"km_total_{$n}"},
                    'traf_pos' => (float) $aforo->{"traf_pos_{$n}"},
                    'traf_real' => (float) $aforo->{"traf_real_{$n}"},
                ];
            }

            return [
                'tn_pos' => (float) ($ind?->{"tn_pos_{$n}"} ?? 0),
                'tn_real' => (float) ($ind?->{"tn_real_{$n}"} ?? 0),
                'km_carga' => (float) ($ind?->{"km_carga_{$n}"} ?? 0),
                'km_vacio' => (float) ($ind?->{"km_vacio_{$n}"} ?? 0),
                'km_total' => (float) ($ind?->{"kms_total_{$n}"} ?? 0),
                'traf_pos' => (float) ($ind?->{"traf_pos_{$n}"} ?? 0),
                'traf_real' => (float) ($ind?->{"traf_real_{$n}"} ?? 0),
            ];
        })->values();

        $data['aforo'] = [
            'id' => $aforo->id,
            'id_carta_porte' => $aforo->id_carta_porte,
            'fecha_parte' => $aforo->fecha_parte?->format('Y-m-d'),
            'fecha_emision' => $aforo->cartaPorte?->fecha_emision?->format('Y-m-d'),
            'fecha_recepcion' => $aforo->cartaPorte?->fecha_recepcion?->format('Y-m-d'),
            'descuento' => (float) $aforo->descuento,
            'flete_mt' => (float) $aforo->flete_mt,
            'flete_mlc' => (float) $aforo->flete_mlc,
            'flete_demora' => (float) $aforo->flete_demora,
            'otros_mt' => (float) $aforo->otros_mt,
            'ingreso_mt' => (float) $aforo->ingreso_mt,
            'id_tasa' => $aforo->id_tasa,
            'tasa' => (float) $aforo->tasa,
            'salario' => (float) $aforo->salario,
            'viajes' => $aforo->viajes,
            'tipo_indicadores' => $aforo->tipo_indicadores,
            'almacenaje_peso' => (float) $aforo->almacenaje_peso,
            'almacenaje_horas' => (float) $aforo->almacenaje_horas,
            'almacenaje_tarifa' => (float) $aforo->almacenaje_tarifa,
            'almacenaje_flete' => (float) $aforo->almacenaje_flete,
            'desc_6' => (float) $aforo->desc_6,
            'dem_carga' => (float) $aforo->dem_carga,
            'dem_descarga' => (float) $aforo->dem_descarga,
            'dem_total' => (float) $aforo->dem_total,
            'fecha_carga' => $aforo->fecha_carga?->format('Y-m-d'),
            'hora_carga_1' => $aforo->hora_carga_1,
            'hora_carga_2' => $aforo->hora_carga_2,
            'fecha_descarga' => $aforo->fecha_descarga?->format('Y-m-d'),
            'hora_descarga_1' => $aforo->hora_descarga_1,
            'hora_descarga_2' => $aforo->hora_descarga_2,
            'tar_dem_1' => (float) $aforo->tar_dem_1,
            'tar_dem_2' => (float) $aforo->tar_dem_2,
            'flete_dem_1' => (float) $aforo->flete_dem_1,
            'flete_dem_2' => (float) $aforo->flete_dem_2,
            'desc_7' => (float) $aforo->desc_7,
            'desc_8' => (float) $aforo->desc_8,
            'tiempo_feriado' => (float) $aforo->tiempo_feriado,
            'tiempo_otros' => (float) $aforo->tiempo_otros,
            'tiempo_movimiento' => (float) $aforo->tiempo_movimiento,
            'tiempo_carga' => (float) $aforo->tiempo_carga,
            'tiempo_descarga' => (float) $aforo->tiempo_descarga,
            'tiempo_total' => (float) $aforo->tiempo_total,
            'recargo_1' => (float) $aforo->recargo_1,
            'recargo_2' => (float) $aforo->recargo_2,
            'recargo_3' => (float) $aforo->recargo_3,
            'recargo_4' => (float) $aforo->recargo_4,
            'recargo_5' => (float) $aforo->recargo_5,
            'lineas' => $lineas,
            'indFilas' => $indFilas,
            'indicadores_totales' => [
                'km_carga_total' => (float) $aforo->km_carga_total,
                'km_vacio_total' => (float) $aforo->km_vacio_total,
                'km_total_total' => (float) $aforo->km_total_total,
                'tn_pos_total' => (float) $aforo->tn_pos_total,
                'tn_real_total' => (float) $aforo->tn_real_total,
                'traf_pos_total' => (float) $aforo->traf_pos_total,
                'traf_real_total' => (float) $aforo->traf_real_total,
            ],
        ];
        $data['title'] = 'Editar Aforo '.$aforo->cartaPorte?->numero;

        return Inertia::render('Aforos/Form', $data);
    }

    /**
     * Catálogos y CP pendientes compartidos por el formulario (create y edit).
     */
    private function datosFormulario(Request $request): array
    {
        $entidadId = (int) session('entidad_activa_id');

        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $inicioMes = Carbon::parse($fechaOperaciones)->startOfMonth()->toDateString();
        $finMes = Carbon::parse($fechaOperaciones)->endOfMonth()->toDateString();

        // CP disponibles a aforar: ya girada, no aforada, del mes de operaciones,
        // no cancelada, por unidad.
        $cartasPendientes = CartaPorte::with([
            'hojaRuta:id,numero,fecha_cierre,id_entidad',
            'solicitud:id,numero,id_lugar_origen,id_lugar_destino,id_moneda,id_cliente,id_producto,id_tipo_carga',
            'cliente',
            'tractivo',
            'arrastre',
            'chofer',
            'chofer2',
            'lugarOrigen',
            'lugarDestino',
            'producto',
            'tipoCarga',
        ])
            ->whereDoesntHave('aforos')
            ->where('cancelada', false)
            ->whereBetween('fecha_emision', [$inicioMes, $finMes])
            ->when($entidadId, fn ($q) => $q->whereHas('hojaRuta.tractivo', fn ($t) => $t->where('id_entidad', $entidadId)))
            ->orderBy('numero')
            ->get();

        // Select preseleccionada (si viene ?carta=<id> desde el grid de CP)
        $cartaPreseleccionada = null;
        if ($request->filled('carta')) {
            $cartaPreseleccionada = CartaPorte::with([
                'hojaRuta:id,numero,fecha_cierre,id_entidad',
                'solicitud:id,numero,id_lugar_origen,id_lugar_destino,id_moneda,id_cliente,id_producto,id_tipo_carga',
                'cliente',
                'tractivo',
                'arrastre',
                'chofer',
                'chofer2',
                'lugarOrigen',
                'lugarDestino',
                'producto',
                'tipoCarga',
            ])->find($request->integer('carta'));
        }

        return [
            'title' => 'Nuevo Aforo',
            'tiposCarga' => TipoCarga::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'codigo']),
            'lugares' => Lugare::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'monedas' => Moneda::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'choferes' => Bolsa::select('id', 'nombre', 'apellidos')->where('activo', true)->orderBy('nombre')->get(),
            'tractivos' => Tractivo::whereNull('fecha_baja')
                ->where(function ($q) {
                    $q->whereNull('id_grupo')->orWhere('id_grupo', '!=', 8);
                })
                ->orderBy('codigo')->get(['id', 'codigo', 'capacidad_toneladas']),
            'arrastres' => Tractivo::whereNull('fecha_baja')->where('id_grupo', 8)
                ->orderBy('codigo')->get(['id', 'codigo', 'capacidad_toneladas']),
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
            'cartasPendientes' => $cartasPendientes,
            'cartaPreseleccionada' => $cartaPreseleccionada,
            'tasas' => $this->cotizador->tasas($entidadId),
            'fechaOperaciones' => $fechaOperaciones,
        ];
    }

    /**
     * Endpoint AJAX de cálculo en vivo de una línea (paridad `aforoCalcularImportes`).
     * Usa el dispatcher del servicio para decidir el cálculo según el tipo de carga.
     */
    public function cotizar(Request $request)
    {
        $validated = $request->validate([
            'moneda' => 'nullable|integer|in:1,2',
            'tipocarga' => 'nullable|integer',
            'distancia' => 'nullable|integer',
            'peso' => 'nullable|numeric',
            'capacidad' => 'nullable|numeric',
            'descuento' => 'nullable|numeric',
            'mlc' => 'nullable|numeric',
            'tipocont' => 'nullable|integer',
            'comb' => 'nullable',
            'origen' => 'nullable|integer',
            'destino' => 'nullable|integer',
            'cliente' => 'nullable|integer',
            'producto' => 'nullable|integer',
            'kms_vacio_tarifa_previa' => 'nullable|numeric',
        ]);

        $resultado = $this->cotizador->calcularLinea(
            moneda: (int) ($validated['moneda'] ?? 1),
            tipocarga: (int) ($validated['tipocarga'] ?? 0),
            distancia: (int) ($validated['distancia'] ?? 0),
            peso: (float) ($validated['peso'] ?? 0),
            capacidad: (float) ($validated['capacidad'] ?? 0),
            descuento: (float) ($validated['descuento'] ?? 0),
            mlc: (float) ($validated['mlc'] ?? 0),
            tipocont: (int) ($validated['tipocont'] ?? 1),
            comb: $validated['comb'] ?? false,
            origen: (int) ($validated['origen'] ?? 0),
            destino: (int) ($validated['destino'] ?? 0),
            cliente: (int) ($validated['cliente'] ?? 0),
            producto: (int) ($validated['producto'] ?? 0),
            kms_vacio_tarifa_previa: (float) ($validated['kms_vacio_tarifa_previa'] ?? 0),
        );

        return response()->json($resultado);
    }

    /**
     * Endpoint AJAX de demora (paridad `aforo/calcular_demora`).
     */
    public function cotizarDemora(Request $request)
    {
        $v = $request->validate([
            'tipocarga1' => 'nullable|integer', 'tipocarga2' => 'nullable|integer',
            'capacidad' => 'nullable|numeric', 'demcarga' => 'nullable|numeric',
            'demdescarga' => 'nullable|numeric', 'descuento1' => 'nullable|numeric',
            'descuento2' => 'nullable|numeric', 'horas' => 'nullable|numeric',
            'conttipo' => 'nullable|integer',
        ]);

        return response()->json($this->cotizador->calcularDemora(
            tipocarga1: (int) ($v['tipocarga1'] ?? 0),
            tipocarga2: (int) ($v['tipocarga2'] ?? 0),
            capacidad: (float) ($v['capacidad'] ?? 0),
            demcarga: (float) ($v['demcarga'] ?? 0),
            demdescarga: (float) ($v['demdescarga'] ?? 0),
            descuento1: (float) ($v['descuento1'] ?? 0),
            descuento2: (float) ($v['descuento2'] ?? 0),
            horas: (float) ($v['horas'] ?? 0),
            conttipo: (int) ($v['conttipo'] ?? 0),
        ));
    }

    /**
     * Endpoint AJAX de almacenaje (paridad `aforo/calcular_almacenaje`).
     */
    public function cotizarAlmacenaje(Request $request)
    {
        $v = $request->validate([
            'alm_peso' => 'nullable|numeric', 'alm_horas' => 'nullable|numeric',
            'descuento' => 'nullable|numeric', 'tipocarga' => 'nullable|integer',
            'tipocont' => 'nullable|integer',
        ]);

        return response()->json($this->cotizador->calcularAlmacenaje(
            alm_peso: (float) ($v['alm_peso'] ?? 0),
            alm_horas: (float) ($v['alm_horas'] ?? 0),
            descuento: (float) ($v['descuento'] ?? 0),
            tipocarga: (int) ($v['tipocarga'] ?? 0),
            tipocont: (int) ($v['tipocont'] ?? 0),
        ));
    }

    /**
     * Endpoint AJAX de salario por rango (paridad `salariochofer/obtener_salario_aforo`).
     */
    public function cotizarSalario(Request $request)
    {
        $v = $request->validate([
            'tipocarga' => 'nullable|integer',
            'capacidad' => 'nullable|numeric',
            'distancia' => 'nullable|integer',
            'ingresos' => 'nullable|numeric',
            'almacenaje' => 'nullable|numeric',
            'idchofer2' => 'nullable|integer',
        ]);

        $entidadId = (int) session('entidad_activa_id');

        return response()->json($this->cotizador->calcularSalario(
            tipocarga: (int) ($v['tipocarga'] ?? 0),
            capacidad: (float) ($v['capacidad'] ?? 0),
            distancia: (int) ($v['distancia'] ?? 0),
            ingresos: (float) ($v['ingresos'] ?? 0),
            almacenaje: (float) ($v['almacenaje'] ?? 0),
            idchofer2: (int) ($v['idchofer2'] ?? 0),
            idEntidad: $entidadId,
        ));
    }

    /**
     * Endpoint AJAX de tiempos (paridad `aforo/calcular_tiempos`).
     */
    public function cotizarTiempos(Request $request)
    {
        $v = $request->validate([
            'movimiento' => 'nullable', 'carga' => 'nullable',
            'descarga' => 'nullable', 'otros' => 'nullable',
        ]);

        return response()->json([
            'ttotal' => $this->cotizador->calcularTiempos(
                movimiento: $v['movimiento'] ?? 0,
                carga: $v['carga'] ?? 0,
                descarga: $v['descarga'] ?? 0,
                otros: $v['otros'] ?? 0,
            ),
        ]);
    }

    /**
     * Endpoint AJAX de indicadores (paridad `aforoCalcularIndicadores`).
     */
    public function cotizarIndicadores(Request $request)
    {
        $v = $request->validate([
            'tipo' => 'nullable|integer|in:1,2,3,4',
            'viajes' => 'nullable|integer|min:1',
            'filas' => 'nullable|array',
        ]);

        return response()->json($this->cotizador->calcularIndicadores(
            tipo: (int) ($v['tipo'] ?? 1),
            viajes: (int) ($v['viajes'] ?? 1),
            filas: $v['filas'] ?? [],
        ));
    }

    /**
     * Guarda un aforo sobre una CP ya girada y no aforada. Persiste el desglose
     * completo del cálculo (tarifas 1-5, descuentos, almacenaje, demora, recargos,
     * tiempos, salario e indicadores) en columnas dedicadas. Además permite
     * corregir los datos generales de la carta de porte (momento de edición).
     */
    public function store(Request $request)
    {
        $validated = $this->validar($request);

        $this->validarLineasCalculadas($request);

        $carta = CartaPorte::whereKey($validated['id_carta_porte'])
            ->where('cancelada', false)
            ->whereDoesntHave('aforos')
            ->firstOrFail();

        $entidadId = (int) session('entidad_activa_id');
        if (! $entidadId) {
            $entidadId = (int) ($carta->tractivo?->id_entidad ?? 0);
        }

        $lineas = collect($request->input('lineas', []));
        $data = $this->armarDataAforo($validated, $lineas, $entidadId);

        $data['fecha_parte'] = $validated['fecha_parte'];
        $data['ingreso_mt'] = $validated['ingreso_mt'];
        $data['flete_mt'] = $validated['flete_mt'];
        $data['flete_mlc'] = $validated['flete_mlc'] ?? 0;
        $data['flete_demora'] = $validated['flete_demora'] ?? 0;
        $data['otros_mt'] = $validated['otros_mt'] ?? 0;
        $data['descuento'] = $validated['descuento'] ?? 0;
        $data['id_user'] = auth()->id();
        $data['fecha_aforada'] = now();

        try {
            DB::transaction(function () use ($data, $request, $carta, $validated) {
                $this->actualizarDatosCarta($carta, $validated);

                $aforo = Aforo::create($data);

                // Indicadores (filas 3-5) en la tabla `indicadores`
                $indicadores = $request->input('indicadores', []);
                if (! empty($indicadores) && ($indicadores['valores'] ?? 0) > 0) {
                    Indicadore::updateOrCreate(
                        ['id_carta_porte' => $aforo->id_carta_porte],
                        $this->armarDataIndicadores($indicadores),
                    );
                } else {
                    Indicadore::where('id_carta_porte', $aforo->id_carta_porte)->delete();
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se pudo guardar el aforo: '.$e->getMessage()]);
        }

        return redirect()->route('aforos.index')->with('success', 'Aforo creado correctamente.');
    }

    /**
     * Actualiza un aforo existente con el desglose recalculado y los datos
     * generales de la CP.
     */
    public function update(Request $request, Aforo $aforo)
    {
        abort_if($aforo->id_factura, 403, 'No es posible editar una carta de porte ya facturada.');

        $validated = $this->validar($request);
        $this->validarLineasCalculadas($request);
        $entidadId = (int) session('entidad_activa_id');
        if (! $entidadId) {
            $entidadId = (int) ($aforo->cartaPorte?->tractivo?->id_entidad ?? 0);
        }

        $lineas = collect($request->input('lineas', []));
        $data = $this->armarDataAforo($validated, $lineas, $entidadId);

        $data['fecha_parte'] = $validated['fecha_parte'];
        $data['ingreso_mt'] = $validated['ingreso_mt'];
        $data['flete_mt'] = $validated['flete_mt'];
        $data['flete_mlc'] = $validated['flete_mlc'] ?? 0;
        $data['flete_demora'] = $validated['flete_demora'] ?? 0;
        $data['otros_mt'] = $validated['otros_mt'] ?? 0;
        $data['descuento'] = $validated['descuento'] ?? 0;

        try {
            DB::transaction(function () use ($aforo, $data, $request, $validated) {
                $this->actualizarDatosCarta($aforo->cartaPorte, $validated);

                $aforo->update($data);

                $indicadores = $request->input('indicadores', []);
                if (! empty($indicadores) && ($indicadores['valores'] ?? 0) > 0) {
                    Indicadore::updateOrCreate(
                        ['id_carta_porte' => $aforo->id_carta_porte],
                        $this->armarDataIndicadores($indicadores),
                    );
                } else {
                    Indicadore::where('id_carta_porte', $aforo->id_carta_porte)->delete();
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se pudo actualizar el aforo: '.$e->getMessage()]);
        }

        return redirect()->route('aforos.index')->with('success', 'Aforo actualizado correctamente.');
    }

    /**
     * Actualiza los datos generales editables de la carta de porte (momento de
     * corrección, paridad legacy: el aforo permite corregir el girado).
     *
     * Fase 4d: la carta ya no persiste cliente/equipo/tipos/productos/lugares/
     * moneda. Esos valores se derivan de la solicitud (cliente/productos/tipos/
     * lugares/moneda) y de la hoja de ruta (equipo/choferes). Por tanto, al
     * corregirlos aquí se propagan a la solicitud y a la hoja de ruta (fuentes
     * de derivación). Los campos que sí pertenecen a la carta
     * (distancia/toneladas/conduce/fechas) se guardan directamente en la carta.
     */
    protected function actualizarDatosCarta(CartaPorte $carta, array $v): void
    {
        $datosCarta = [
            'distancia' => $v['distancia'] ?? $carta->distancia,
            'toneladas' => $v['toneladas'] ?? $carta->toneladas,
            'conduce' => $v['conduce'] ?? $carta->conduce,
            'fecha_emision' => $v['fecha_emision'] ?? $carta->fecha_emision,
            'fecha_recepcion' => $v['fecha_recepcion'] ?? $carta->fecha_recepcion,
        ];

        $carta->update($datosCarta);

        // Cliente/productos/tipos/lugares/moneda → solicitud (fuente de derivación)
        $solicitud = $carta->solicitud;
        if ($solicitud) {
            $datosSol = [];
            foreach (['id_cliente', 'id_producto', 'id_producto2', 'id_tipo_carga', 'id_tipo_carga2', 'id_lugar_origen', 'id_lugar_destino', 'id_moneda'] as $campo) {
                if (array_key_exists($campo, $v)) {
                    $datosSol[$campo] = $v[$campo];
                }
            }
            if ($datosSol) {
                $solicitud->update($datosSol);
            }
        }

        // Equipo/choferes → hoja de ruta (fuente de derivación)
        $hoja = $carta->hojaRuta;
        if ($hoja) {
            $datosHoja = [];
            foreach (['id_tractivo', 'id_arrastre', 'id_chofer', 'id_chofer2'] as $campo) {
                if (array_key_exists($campo, $v)) {
                    $datosHoja[$campo] = $v[$campo];
                }
            }
            if ($datosHoja) {
                $hoja->update($datosHoja);
            }
        }
    }

    /**
     * Valida que cada línea de tarifa esté calculada (flete > 0), salvo que el
     * tipo de carga sea acuerdo/importe (22) o acuerdo/viaje (23).
     */
    protected function validarLineasCalculadas(Request $request): void
    {
        $tiposAcuerdo = [22, 23];
        $lineas = collect($request->input('lineas', []));

        $sinCalcular = [];
        foreach ($lineas as $i => $linea) {
            $tipo = (int) ($linea['id_tipo_carga'] ?? 0);
            if ($tipo <= 0 || in_array($tipo, $tiposAcuerdo)) {
                continue;
            }
            if ((float) ($linea['flete_mt'] ?? 0) <= 0) {
                $sinCalcular[] = 'Línea '.($i + 1);
            }
        }

        if (! empty($sinCalcular)) {
            throw ValidationException::withMessages([
                'lineas' => 'Debe calcular las tarifas de: '.implode(', ', $sinCalcular),
            ]);
        }
    }

    protected function validar(Request $request): array
    {
        return $request->validate([
            'id_carta_porte' => 'required|exists:cartas_porte,id',
            'fecha_parte' => 'required|date',
            'fecha_emision' => 'nullable|date',
            'fecha_recepcion' => 'nullable|date',
            'flete_mt' => 'required|numeric|min:0',
            'flete_mlc' => 'nullable|numeric|min:0',
            'flete_demora' => 'nullable|numeric|min:0',
            'otros_mt' => 'nullable|numeric|min:0',
            'ingreso_mt' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'id_tasa' => 'nullable|exists:tasas,id',
            'tasa' => 'nullable|numeric',
            'salario' => 'nullable|numeric',
            'viajes' => 'nullable|integer|min:1',
            'tipo_indicadores' => 'nullable|integer|in:1,2,3,4',

            // Datos generales editables de la CP
            'id_cliente' => 'nullable|exists:clientes,id',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'id_arrastre' => 'nullable|exists:tractivos,id',
            'id_chofer' => 'nullable|exists:bolsa,id',
            'id_chofer2' => 'nullable|exists:bolsa,id',
            'id_lugar_origen' => 'nullable|exists:lugares,id',
            'id_lugar_destino' => 'nullable|exists:lugares,id',
            'id_producto' => 'nullable|exists:productos,id',
            'id_tipo_carga' => 'nullable|exists:tipos_cargas,id',
            'id_moneda' => 'nullable|exists:monedas,id',
            'distancia' => 'nullable|integer|min:0',
            'toneladas' => 'nullable|numeric|min:0',
            'conduce' => 'nullable|string|max:150',
        ]);
    }

    protected function armarDataAforo(array $v, $lineas, int $entidadId): array
    {
        // Totalizadores legacy (paridad aforoCalcularValores):
        // fletemtt = Σ fletemt1..5 + almflete ; otrosmtt = Σ recargo1..5
        $data = [
            'id_carta_porte' => $v['id_carta_porte'],
            'id_tasa' => $v['id_tasa'] ?? null,
            'tasa' => $v['tasa'] ?? 0,
            'salario' => $v['salario'] ?? 0,
            'viajes' => $v['viajes'] ?? 1,
            'tipo_indicadores' => $v['tipo_indicadores'] ?? 1,
        ];

        foreach (range(1, 5) as $n) {
            $linea = $lineas->get($n - 1) ?? [];
            $data["id_tipo_carga_{$n}"] = $linea['id_tipo_carga'] ?? null;
            $data["distancia_{$n}"] = (float) ($linea['distancia'] ?? 0);
            $data["tarifa_mt_{$n}"] = (float) ($linea['tarifa_mt'] ?? 0);
            $data["flete_mt_{$n}"] = (float) ($linea['flete_mt'] ?? 0);
            $data["flete_mlc_{$n}"] = (float) ($linea['flete_mlc'] ?? 0);
            $data["peso_cobrar_{$n}"] = (float) ($linea['peso_cobrar'] ?? 0);
            $data["desc_{$n}"] = (float) ($linea['descuento'] ?? 0);
        }

        // desc_6 (almacenaje), desc_7 (demora carga), desc_8 (demora descarga)
        $data['desc_6'] = (float) ($v['desc_6'] ?? 0);
        $data['desc_7'] = (float) ($v['desc_7'] ?? 0);
        $data['desc_8'] = (float) ($v['desc_8'] ?? 0);

        // Almacenaje
        $data['almacenaje_peso'] = (float) ($v['almacenaje_peso'] ?? 0);
        $data['almacenaje_horas'] = (float) ($v['almacenaje_horas'] ?? 0);
        $data['almacenaje_tarifa'] = (float) ($v['almacenaje_tarifa'] ?? 0);
        $data['almacenaje_flete'] = (float) ($v['almacenaje_flete'] ?? 0);

        // Demora
        $data['tar_dem_1'] = (float) ($v['tar_dem_1'] ?? 0);
        $data['tar_dem_2'] = (float) ($v['tar_dem_2'] ?? 0);
        $data['flete_dem_1'] = (float) ($v['flete_dem_1'] ?? 0);
        $data['flete_dem_2'] = (float) ($v['flete_dem_2'] ?? 0);
        $data['dem_carga'] = (float) ($v['dem_carga'] ?? 0);
        $data['dem_descarga'] = (float) ($v['dem_descarga'] ?? 0);
        $data['dem_total'] = (float) ($v['dem_total'] ?? 0);
        $data['fecha_carga'] = $v['fecha_carga'] ?? null;
        $data['hora_carga_1'] = $v['hora_carga_1'] ?? null;
        $data['hora_carga_2'] = $v['hora_carga_2'] ?? null;
        $data['fecha_descarga'] = $v['fecha_descarga'] ?? null;
        $data['hora_descarga_1'] = $v['hora_descarga_1'] ?? null;
        $data['hora_descarga_2'] = $v['hora_descarga_2'] ?? null;

        // Tiempos
        $data['tiempo_otros'] = (float) ($v['tiempo_otros'] ?? 0);
        $data['tiempo_movimiento'] = (float) ($v['tiempo_movimiento'] ?? 0);
        $data['tiempo_carga'] = (float) ($v['tiempo_carga'] ?? 0);
        $data['tiempo_descarga'] = (float) ($v['tiempo_descarga'] ?? 0);
        $data['tiempo_total'] = (float) ($v['tiempo_total'] ?? 0);
        $data['tiempo_feriado'] = (float) ($v['tiempo_feriado'] ?? 0);

        // Recargos
        foreach (range(1, 5) as $n) {
            $data["recargo_{$n}"] = (float) ($v["recargo_{$n}"] ?? 0);
        }

        // Indicadores filas 1-2 + totales
        $ind = $v['indicadores'] ?? [];
        foreach (['tn_pos', 'tn_real', 'km_carga', 'km_vacio', 'km_total', 'traf_pos', 'traf_real'] as $campo) {
            $data["{$campo}_1"] = (float) ($ind["{$campo}_1"] ?? 0);
            $data["{$campo}_2"] = (float) ($ind["{$campo}_2"] ?? 0);
            $data["{$campo}_total"] = (float) ($ind["{$campo}_total"] ?? 0);
        }

        return $data;
    }

    protected function armarDataIndicadores(array $ind): array
    {
        $data = [];
        foreach (range(3, 5) as $n) {
            $data["tn_pos_{$n}"] = (float) ($ind["tn_pos_{$n}"] ?? 0);
            $data["tn_real_{$n}"] = (float) ($ind["tn_real_{$n}"] ?? 0);
            $data["km_carga_{$n}"] = (float) ($ind["km_carga_{$n}"] ?? 0);
            $data["km_vacio_{$n}"] = (float) ($ind["km_vacio_{$n}"] ?? 0);
            $data["kms_total_{$n}"] = (float) ($ind["km_total_{$n}"] ?? 0);
            $data["traf_real_{$n}"] = (float) ($ind["traf_real_{$n}"] ?? 0);
            $data["traf_pos_{$n}"] = (float) ($ind["traf_pos_{$n}"] ?? 0);
        }

        return $data;
    }
}
