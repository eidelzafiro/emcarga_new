<?php

namespace App\Http\Controllers;

use App\Models\Aforo;
use App\Models\AforoIndicadore;
use App\Models\AforoLinea;
use App\Models\Bolsa;
use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Entidad;
use App\Models\HojasRuta;
use App\Models\Lugare;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\SolicitudesServicio;
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

        // Mes/año de la fecha de parte a revisar: SIEMPRE el mes de operaciones
        // (el grid NO permite cambiar de mes/año).
        $fechaOperaciones = session('fecha_operaciones') ?? now()->toDateString();
        $anio = (int) Carbon::parse($fechaOperaciones)->year;
        $mes = (int) Carbon::parse($fechaOperaciones)->month;

        $query = Aforo::with([
            'cartaPorte:id,numero,id_hoja_ruta,id_solicitud,distancia',
            'cartaPorte.cliente',
            'cartaPorte.tractivo',
            'cartaPorte.hojaRuta:id,numero,id_entidad',
            'cartaPorte.lugarOrigen',
            'cartaPorte.lugarDestino',
            'factura:id,numero',
        ]);

        // Solo el mes/año seleccionado de fecha de parte
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

        if ($entidadId) {
            $this->scopeEntidad($query);
        }

        // Opciones para los filtros: solo de la entidad actual en el mes/año seleccionado
        $base = Aforo::query()->with([
            'cartaPorte:id,id_hoja_ruta,id_solicitud',
            'cartaPorte.hojaRuta:id,id_tractivo,id_chofer,id_chofer2',
            'cartaPorte.solicitud:id,id_cliente',
        ])
            ->whereYear('fecha_parte', $anio)->whereMonth('fecha_parte', $mes);
        if ($entidadId) {
            $this->scopeEntidad($base);
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
            'filters' => $request->only(['search', 'cliente', 'chofer', 'equipo']),
            'filtros' => $filtros,
            'fechaOperaciones' => $fechaOperaciones,
            'mesSeleccionado' => $mes,
            'anioSeleccionado' => $anio,
        ]);
    }

    /**
     * Ids de entidad permitidos para la entidad activa (ella + sus subordinadas),
     * usados para filtrar los registros visibles y autorizar el acceso.
     */
    private function entidadesPermitidas(): array
    {
        $entidadId = (int) session('entidad_activa_id');
        if (! $entidadId) {
            return [];
        }

        return collect(Entidad::subEntidadesIds($entidadId))
            ->push($entidadId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Autoriza el acceso a un aforo verificando que la CP pertenezca a una
     * entidad permitida para la entidad activa. La entidad de la CP se deriva
     * de su hoja de ruta (id_entidad), su tractivo o su solicitud.
     */
    private function autorizarEntidadAforo(Aforo $aforo): void
    {
        $ids = $this->entidadesPermitidas();
        if (empty($ids)) {
            return;
        }

        $pertenece = Aforo::whereKey($aforo->id)
            ->whereHas('cartaPorte', fn ($q) => $this->whereCartaEnEntidades($q, $ids))
            ->exists();

        if (! $pertenece) {
            abort(403, 'No tiene permiso para acceder a esta carta de porte.');
        }
    }

    /**
     * Restringe una consulta de cartas de porte a las entidades dadas. La
     * entidad de una CP puede venir de su hoja de ruta (id_entidad directo o
     * vía tractivo) o de su solicitud (aforos "desde cero" sin HR).
     */
    private function whereCartaEnEntidades($q, array $ids): void
    {
        $q->where(function ($w) use ($ids) {
            $w->whereHas('hojaRuta', fn ($h) => $h->whereIn('id_entidad', $ids))
                ->orWhereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $ids))
                ->orWhereHas('solicitud', fn ($s) => $s->whereIn('id_entidad', $ids));
        });
    }

    /**
     * Filtra una consulta de aforos por las entidades permitidas.
     */
    private function scopeEntidad($query): void
    {
        $ids = $this->entidadesPermitidas();
        if (! empty($ids)) {
            $query->whereHas('cartaPorte', fn ($q) => $this->whereCartaEnEntidades($q, $ids));
        }
    }

    public function show(Aforo $aforo)
    {
        $this->autorizarEntidadAforo($aforo);

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

        $this->autorizarEntidadAforo($aforo);

        $aforo->load('cartaPorte.tractivo');
        $request = request();

        // Cargar la CP completa del aforo para poder editar sus datos generales
        $carta = $aforo->cartaPorte;
        $carta->load([
            'hojaRuta:id,numero,fecha_cierre,id_entidad,id_tractivo,id_arrastre,id_chofer,id_chofer2',
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

        // Líneas de tarifa desde la tabla hija `aforo_lineas` (D1)
        $aforo->load('lineas');
        $lineasPorPos = $aforo->lineas->keyBy('posicion');

        $lineas = collect(range(1, 5))->map(function ($n) use ($lineasPorPos) {
            $l = $lineasPorPos->get($n);

            return [
                'id_tipo_carga' => $l?->id_tipo_carga,
                'peso_cobrar' => (float) ($l?->peso_cobrar ?? 0),
                'distancia' => (float) ($l?->distancia ?? 0),
                'descuento' => (float) ($l?->descuento ?? 0),
                'tarifa_mt' => (float) ($l?->tarifa_mt ?? 0),
                'flete_mt' => (float) ($l?->flete_mt ?? 0),
                'flete_mlc' => (float) ($l?->flete_mlc ?? 0),
            ];
        })->values();

        // Indicadores desde la tabla hija `aforo_indicadores` (posiciones 1-7, D1)
        $aforo->load('indicadoresFilas');
        $indPorPos = $aforo->indicadoresFilas->keyBy('posicion');

        $indFilas = collect(range(1, 7))->map(function ($n) use ($indPorPos) {
            $f = $indPorPos->get($n);

            return [
                'tn_pos' => (float) ($f?->tn_pos ?? 0),
                'tn_real' => (float) ($f?->tn_real ?? 0),
                'km_carga' => (float) ($f?->km_carga ?? 0),
                'km_vacio' => (float) ($f?->km_vacio ?? 0),
                'km_total' => (float) ($f?->km_total ?? 0),
                'traf_pos' => (float) ($f?->traf_pos ?? 0),
                'traf_real' => (float) ($f?->traf_real ?? 0),
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
            'hojaRuta:id,numero,fecha_cierre,id_entidad,id_tractivo,id_arrastre,id_chofer,id_chofer2',
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
            ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $this->entidadesPermitidas())))
            ->orderBy('numero')
            ->get();

        // Select preseleccionada (si viene ?carta=<id> desde el grid de CP)
        $cartaPreseleccionada = null;
        if ($request->filled('carta')) {
            $cartaPreseleccionada = CartaPorte::with([
                'hojaRuta:id,numero,fecha_cierre,id_entidad,id_tractivo,id_arrastre,id_chofer,id_chofer2',
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
                ->whereKey($request->integer('carta'))
                ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $this->entidadesPermitidas())))
                ->first();
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
            'hojasRuta' => HojasRuta::select('id', 'numero', 'fecha_emision', 'fecha_cierre', 'id_tractivo', 'id_arrastre', 'id_chofer', 'id_chofer2', 'id_entidad')
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
     * Endpoint AJAX de horas de demora desde H1/H2 (paridad `aforo/dif_horas`).
     * Calcula las horas transcurridas y descuenta las horas libres según peso.
     */
    public function cotizarDifHoras(Request $request)
    {
        $v = $request->validate([
            'hora1' => 'nullable|string', 'hora2' => 'nullable|string',
            'peso' => 'nullable|numeric',
        ]);

        return response()->json([
            'horas' => $this->cotizador->difHoras(
                hora1: (string) ($v['hora1'] ?? ''),
                hora2: (string) ($v['hora2'] ?? ''),
                peso: (float) ($v['peso'] ?? 0),
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

        $carta = null;
        if ($validated['id_carta_porte'] ?? null) {
            $carta = CartaPorte::whereKey($validated['id_carta_porte'])
                ->where('cancelada', false)
                ->whereDoesntHave('aforos')
                ->when(! empty($this->entidadesPermitidas()), fn ($q) => $q->whereHas('hojaRuta.tractivo', fn ($t) => $t->whereIn('id_entidad', $this->entidadesPermitidas())))
                ->firstOrFail();
        } else {
            // Aforo "desde cero": se crea la carta de porte (con su solicitud)
            // a partir de los datos generales del formulario.
            $carta = $this->crearCartaDesdeCero($validated);
        }

        // El aforo siempre apunta a una carta existente (creada o seleccionada).
        $validated['id_carta_porte'] = $carta->id;

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

                $this->guardarLineas($aforo, collect($request->input('lineas', [])));
                $this->guardarIndicadoresFilas($aforo, $request->input('indicadoresFilas', []));

                // Al aforar, la solicitud pasa a ejecutada (si la carta se aforó).
                $carta->solicitud?->recalcularEstado();
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

        $this->autorizarEntidadAforo($aforo);

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

                $this->guardarLineas($aforo, collect($request->input('lineas', [])));
                $this->guardarIndicadoresFilas($aforo, $request->input('indicadoresFilas', []));

                $aforo->cartaPorte?->solicitud?->recalcularEstado();
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se pudo actualizar el aforo: '.$e->getMessage()]);
        }

        return redirect()->route('aforos.index')->with('success', 'Aforo actualizado correctamente.');
    }

    /**
     * Crea una carta de porte "desde cero" (aforo sin CP previa): genera la
     * solicitud con los datos generales y la carta vinculada a ella.
     */
    protected function crearCartaDesdeCero(array $v): CartaPorte
    {
        $fecha = $v['fecha_emision'] ?? $v['fecha_parte'] ?? now()->toDateString();

        $solicitud = SolicitudesServicio::create([
            'numero' => $this->siguienteNumeroSolicitud($fecha),
            'id_entidad' => session('entidad_activa_id') ?: null,
            'id_user' => auth()->id(),
            'fecha_solicitud' => $fecha,
            'estado' => 'pendiente',
            'id_cliente' => $v['id_cliente'] ?? null,
            'id_lugar_origen' => $v['id_lugar_origen'] ?? null,
            'id_lugar_destino' => $v['id_lugar_destino'] ?? null,
            'id_producto' => $v['id_producto'] ?? null,
            'id_tipo_carga' => $v['id_tipo_carga'] ?? null,
            'id_moneda' => $v['id_moneda'] ?? null,
            'peso1' => $v['peso1'] ?? 0,
            'peso2' => $v['peso2'] ?? 0,
            'distancia' => $v['distancia'] ?? null,
        ]);

        return CartaPorte::create([
            'numero' => $v['numero_carta'] ?? $this->siguienteNumeroCarta(),
            'id_hoja_ruta' => $v['id_hoja_ruta'] ?? null,
            'id_solicitud' => $solicitud->id,
            'fecha_emision' => $fecha,
            'fecha_parte' => $v['fecha_parte'] ?? $fecha,
            'toneladas' => (float) ($v['peso1'] ?? 0) + (float) ($v['peso2'] ?? 0),
            'peso1' => $v['peso1'] ?? 0,
            'peso2' => $v['peso2'] ?? 0,
            'distancia' => $v['distancia'] ?? null,
            'conduce' => $v['conduce'] ?? null,
            'estado' => 'emitida',
            'cancelada' => false,
            'id_user' => auth()->id(),
        ]);
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

    private function siguienteNumeroCarta(): string
    {
        $anio = now()->year;
        $base = 'CP-'.$anio.'-';
        $ultimo = CartaPorte::where('numero', 'like', $base.'%')
            ->orderBy('numero', 'desc')
            ->value('numero');
        $sec = $ultimo ? ((int) substr($ultimo, strlen($base))) + 1 : 1;

        return $base.str_pad((string) $sec, 5, '0', STR_PAD_LEFT);
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
            'id_carta_porte' => 'nullable|exists:cartas_porte,id',
            'numero_carta' => 'nullable|string|max:20',
            'fecha_parte' => ['required', 'date', $mesOperaciones()],
            'fecha_emision' => ['nullable', 'date', $mesOperaciones()],
            'fecha_recepcion' => ['nullable', 'date'],
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

        // Totales de indicadores (resumen denormalizado para grids). Las filas
        // individuales (1-7) viven en la tabla hija `aforo_indicadores` (D1).
        $ind = $v['indicadores'] ?? [];
        foreach (['tn_pos', 'tn_real', 'km_carga', 'km_vacio', 'km_total', 'traf_pos', 'traf_real'] as $campo) {
            $data["{$campo}_total"] = (float) ($ind["{$campo}_total"] ?? 0);
        }

        return $data;
    }

    /**
     * Guarda las líneas de tarifa (1-5) en `aforo_lineas` (D1). Reemplaza las
     * filas existentes del aforo (delete + insert) para reflejar la edición.
     */
    protected function guardarLineas(Aforo $aforo, $lineas): void
    {
        AforoLinea::where('id_aforo', $aforo->id)->delete();

        foreach ($lineas as $i => $linea) {
            $posicion = $i + 1;
            $tc = $linea['id_tipo_carga'] ?? null;
            $dist = (float) ($linea['distancia'] ?? 0);
            $tar = (float) ($linea['tarifa_mt'] ?? 0);
            $fletemt = (float) ($linea['flete_mt'] ?? 0);
            $peso = (float) ($linea['peso_cobrar'] ?? 0);

            // Línea vacía (todo 0) → se omite.
            if (($tc ?? 0) == 0 && $dist == 0 && $tar == 0 && $fletemt == 0 && $peso == 0) {
                continue;
            }

            AforoLinea::create([
                'id_aforo' => $aforo->id,
                'posicion' => $posicion,
                'id_tipo_carga' => $tc ?: null,
                'distancia' => $dist ?: null,
                'peso_cobrar' => $peso ?: null,
                'descuento' => (float) ($linea['descuento'] ?? 0) ?: null,
                'tarifa_mt' => $tar ?: null,
                'flete_mt' => $fletemt ?: null,
                'flete_mlc' => (float) ($linea['flete_mlc'] ?? 0) ?: null,
            ]);
        }
    }

    /**
     * Guarda las filas de indicadores (1-7) en `aforo_indicadores` (D1).
     */
    protected function guardarIndicadoresFilas(Aforo $aforo, array $filas): void
    {
        AforoIndicadore::where('id_aforo', $aforo->id)->delete();

        foreach ($filas as $i => $fila) {
            $posicion = $i + 1;
            $datos = [
                'tn_pos' => (float) ($fila['tn_pos'] ?? 0),
                'tn_real' => (float) ($fila['tn_real'] ?? 0),
                'km_carga' => (float) ($fila['km_carga'] ?? 0),
                'km_vacio' => (float) ($fila['km_vacio'] ?? 0),
                'km_total' => (float) ($fila['km_total'] ?? 0),
                'traf_pos' => (float) ($fila['traf_pos'] ?? 0),
                'traf_real' => (float) ($fila['traf_real'] ?? 0),
            ];

            if (! collect($datos)->contains(fn ($v) => $v != 0)) {
                continue;
            }

            AforoIndicadore::create([
                'id_aforo' => $aforo->id,
                'posicion' => $posicion,
                ...$datos,
            ]);
        }
    }
}
