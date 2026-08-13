<?php

namespace App\Http\Controllers;

use App\Models\Aforo;
use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Entidad;
use App\Models\Lugare;
use App\Models\Producto;
use App\Models\TipoCarga;
use App\Models\Tractivo;
use App\Services\AforoCotizadorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AforosController extends Controller
{
    public function __construct(
        private readonly AforoCotizadorService $cotizador,
    ) {}

    public function index(Request $request)
    {
        $query = Aforo::with([
            'cartaPorte:id,numero,id_cliente,id_tractivo',
            'cartaPorte.cliente:id,nombre',
            'cartaPorte.tractivo:id,codigo,id_entidad',
            'factura:id,numero',
        ]);

        $query->when($request->anio, fn ($q, $v) => $q->whereYear('fecha_parte', $v))
            ->when($request->mes, fn ($q, $v) => $q->whereMonth('fecha_parte', $v));

        $query->when($request->search, function ($q, $s) {
            $q->where(function ($q2) use ($s) {
                $q2->whereHas('cartaPorte', fn ($q3) => $q3->where('numero', 'like', "%{$s}%"))
                    ->orWhereHas('cartaPorte.cliente', fn ($q3) => $q3->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('cartaPorte.tractivo', fn ($q3) => $q3->where('codigo', 'like', "%{$s}%"));
            });
        });

        $query->when($request->estado, function ($q, $v) {
            if ($v === 'pendiente') {
                $q->whereNull('id_factura')->whereNull('id_prefactura');
            } elseif ($v === 'facturado') {
                $q->whereNotNull('id_factura');
            } elseif ($v === 'prefacturado') {
                $q->whereNull('id_factura')->whereNotNull('id_prefactura');
            }
        });

        $entidadId = (int) session('entidad_activa_id');
        if ($entidadId) {
            $ids = collect(Entidad::subEntidadesIds($entidadId))
                ->push($entidadId)
                ->unique()
                ->values()
                ->all();
            $query->whereHas('cartaPorte.tractivo', fn ($q) => $q->whereIn('id_entidad', $ids));
        }

        $aforos = $query->orderByDesc('fecha_parte')->orderByDesc('id')->paginate(20);

        return Inertia::render('Aforos/Index', [
            'title' => 'Aforos',
            'aforos' => $aforos,
            'filters' => $request->only(['search', 'estado', 'anio', 'mes']),
        ]);
    }

    public function show(Aforo $aforo)
    {
        $aforo->load([
            'cartaPorte.cliente:id,nombre,codigo',
            'cartaPorte.tractivo:id,codigo,placa,id_entidad',
            'cartaPorte.chofer:id,nombrecompleto',
            'cartaPorte.chofer2:id,nombrecompleto',
            'cartaPorte.lugarOrigen:id,nombre',
            'cartaPorte.lugarDestino:id,nombre',
            'cartaPorte.producto:id,nombre',
            'cartaPorte.tipoCarga:id,nombre',
            'factura:id,numero,estado',
            'prefactura:id,numero,estado',
            'user:id,name',
        ]);

        return Inertia::render('Aforos/Show', [
            'title' => 'Aforo '.$aforo->cartaPorte?->numero,
            'aforo' => $aforo,
        ]);
    }

    /**
     * Formulario de aforo (creación). Carga catálogos para las 5 líneas + globales.
     */
    public function create()
    {
        return Inertia::render('Aforos/Form', [
            'title' => 'Nuevo Aforo',
            'tiposCarga' => TipoCarga::orderBy('nombre')->get(['id', 'nombre']),
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'codigo']),
            'tractivos' => Tractivo::whereNull('fecha_baja')
                ->where(function ($q) {
                    // Excluye arrastres (tractivos grupo ARRASTRES, id_grupo=8)
                    $q->whereNull('id_grupo')->orWhere('id_grupo', '!=', 8);
                })
                ->orderBy('codigo')->get(['id', 'codigo', 'capacidad_toneladas']),
            'lugares' => Lugare::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'hojasRuta' => [],
            'siguiente_cp' => $this->siguienteNumeroCp(),
        ]);
    }

    /**
     * Endpoint AJAX de cálculo en vivo. Llama al servicio replicando el legacy.
     * Devuelve la tarifa calculada para una línea.
     */
    public function cotizar(Request $request)
    {
        $validated = $request->validate([
            'moneda' => 'nullable|integer|in:1,2',
            'tipocarga' => 'nullable|integer',
            'distancia' => 'nullable|integer',
            'peso' => 'nullable|numeric',
            'descuento' => 'nullable|numeric',
            'mlc' => 'nullable|numeric',
        ]);

        $resultado = $this->cotizador->tarifa(
            moneda: (int) ($validated['moneda'] ?? 1),
            tipocarga: (int) ($validated['tipocarga'] ?? 2),
            distancia: (int) ($validated['distancia'] ?? 0),
            peso: (float) ($validated['peso'] ?? 0),
            descuento: (float) ($validated['descuento'] ?? 0),
            mlc: (float) ($validated['mlc'] ?? 0),
        );

        return response()->json($resultado);
    }

    /**
     * Guarda un aforo: crea la carta de porte (girado) + el aforo con totales.
     * Los totales pueden venir pre-calculados desde el frontend (paridad legacy:
     * el frontend calcula en vivo y envía los valores).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'nullable|string',
            'fecha_parte' => 'required|date',
            'id_cliente' => 'required|exists:clientes,id',
            'id_tractivo' => 'nullable|exists:tractivos,id',
            'id_arrastre' => 'nullable|exists:tractivos,id',
            'id_chofer' => 'nullable|integer',
            'id_chofer2' => 'nullable|integer',
            'id_lugar_origen' => 'nullable|exists:lugares,id',
            'id_lugar_destino' => 'nullable|exists:lugares,id',
            'id_producto' => 'nullable|exists:productos,id',
            'id_tipo_carga' => 'nullable|exists:tipos_cargas,id',
            'id_moneda' => 'nullable|exists:monedas,id',
            'distancia' => 'nullable|numeric',
            'toneladas' => 'nullable|numeric',
            'flete_mt' => 'required|numeric|min:0',
            'flete_mlc' => 'nullable|numeric|min:0',
            'flete_demora' => 'nullable|numeric|min:0',
            'otros_mt' => 'nullable|numeric|min:0',
            'ingreso_mt' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric',
        ]);

        $validated['numero'] ??= $this->siguienteNumeroCp();

        $idEntidad = (int) session('entidad_activa_id');
        $idTractivo = $validated['id_tractivo'] ?? null;
        if ($idTractivo && ! $idEntidad) {
            $idEntidad = (int) Tractivo::whereKey($idTractivo)->value('id_entidad');
        }

        try {
            DB::transaction(function () use ($validated) {
                $cartaPorte = CartaPorte::create([
                    'numero' => $validated['numero'],
                    'id_cliente' => $validated['id_cliente'],
                    'id_tractivo' => $validated['id_tractivo'] ?? null,
                    'id_arrastre' => $validated['id_arrastre'] ?? null,
                    'id_chofer' => $validated['id_chofer'] ?? null,
                    'id_chofer2' => $validated['id_chofer2'] ?? null,
                    'id_lugar_origen' => $validated['id_lugar_origen'] ?? null,
                    'id_lugar_destino' => $validated['id_lugar_destino'] ?? null,
                    'id_producto' => $validated['id_producto'] ?? null,
                    'id_tipo_carga' => $validated['id_tipo_carga'] ?? null,
                    'id_moneda' => $validated['id_moneda'] ?? null,
                    'distancia' => $validated['distancia'] ?? 0,
                    'toneladas' => $validated['toneladas'] ?? 0,
                    'fecha_parte' => $validated['fecha_parte'],
                    'fecha_emision' => $validated['fecha_parte'],
                    'estado' => 'abierta',
                    'cancelada' => false,
                    'id_user' => auth()->id(),
                ]);

                Aforo::create([
                    'id_carta_porte' => $cartaPorte->id,
                    'fecha_parte' => $validated['fecha_parte'],
                    'flete_mt' => $validated['flete_mt'],
                    'flete_mlc' => $validated['flete_mlc'] ?? 0,
                    'flete_demora' => $validated['flete_demora'] ?? 0,
                    'otros_mt' => $validated['otros_mt'] ?? 0,
                    'ingreso_mt' => $validated['ingreso_mt'],
                    'descuento' => $validated['descuento'] ?? 0,
                    'refactura' => false,
                    'id_user' => auth()->id(),
                ]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se pudo guardar el aforo: '.$e->getMessage()]);
        }

        return redirect()->route('aforos.index')->with('success', 'Aforo creado correctamente.');
    }

    /**
     * Correlativo de carta de porte (girado). El legacy usa nrocp por unidad;
     * aquí se genera el siguiente entero libre.
     */
    private function siguienteNumeroCp(): int
    {
        $max = (int) CartaPorte::withTrashed()
            ->whereRaw('numero REGEXP "^[0-9]+$"')
            ->max(DB::raw('CAST(numero AS UNSIGNED)'));

        return $max + 1;
    }
}
