<?php

namespace App\Http\Controllers;

use App\Models\CartaPorte;
use App\Models\Bolsa;
use App\Models\Cliente;
use App\Models\HojasRuta;
use App\Models\Lugare;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\SolicitudesServicio;
use App\Models\TipoCarga;
use App\Models\Tractivo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SolicitudesController extends Controller
{
    public function index(Request $request)
    {
        $entidadId = (int) session('entidad_activa_id');

        $solicitudes = SolicitudesServicio::with([
            'cliente:id,nombre',
            'lugarOrigen:id,nombre',
            'lugarDestino:id,nombre',
            'producto:id,codigo,nombre',
            'producto2:id,codigo,nombre',
            'tipoCarga:id,codigo,nombre',
            'tipoCarga2:id,codigo,nombre',
            'moneda:id,codigo,nombre,simbolo',
            'cartasPorte' => fn ($q) => $q->where('estado', '!=', 'cancelada'),
        ])
            ->when($request->search, fn ($q, $s) => $q->where('numero', 'like', "%{$s}%")
                ->orWhereHas('cliente', fn ($q2) => $q2->where('nombre', 'like', "%{$s}%")))
            ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
            ->orderBy('fecha_planificada', 'asc')
            ->orderBy('numero', 'asc')
            ->paginate(20);

        // Seguimiento de toneladas: suma de las toneladas (pesos) de las
        // cartas de porte vigentes. Una solicitud solo se marca realizada
        // cuando hay meta definida (peso1+peso2 > 0) y está cubierta.
        foreach ($solicitudes as $sol) {
            $total = (float) ($sol->peso1 ?? 0) + (float) ($sol->peso2 ?? 0);
            $ejecutado = (float) $sol->cartasPorte->sum('toneladas');
            $sol->toneladas_total = $total;
            $sol->toneladas_ejecutadas = $ejecutado;
            $sol->toneladas_pendientes = max(0, $total - $ejecutado);
            $sol->estado_cumplimiento = match (true) {
                $total <= 0 => 'pendiente',
                $ejecutado <= 0 => 'pendiente',
                $sol->toneladas_pendientes > 0 => 'en_proceso',
                default => 'realizada',
            };
        }

        return Inertia::render('Solicitudes/Index', [
            'title' => 'Solicitudes de Servicio',
            'solicitudes' => $solicitudes,
            'clientes' => Cliente::where('activo', true)
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'lugares' => Lugare::where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'productos' => Producto::where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'tiposCargas' => TipoCarga::where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre']),
            'monedas' => Moneda::where('activo', true)
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre', 'simbolo']),
            'filters' => $request->only(['search']),
            'catalogosCarta' => $this->catalogosCarta($entidadId),
        ]);
    }

    /**
     * Catálogos para el formulario de emisión de carta de porte desde la
     * solicitud (réplica del legacy: hoja ruta, equipos, choferes, etc.).
     */
    private function catalogosCarta(int $entidadId): array
    {
        $hoy = now()->toDateString();

        return [
            'hojasRuta' => HojasRuta::select('id', 'numero', 'fecha_emision', 'fecha_cierre', 'id_tractivo', 'id_arrastre', 'id_chofer', 'id_chofer2', 'id_entidad', 'id_cliente')
                ->with(['tractivo:id,codigo', 'arrastre:id,codigo', 'chofer:id,nombre,apellidos', 'chofer2:id,nombre,apellidos'])
                ->where(fn ($q) => $q->whereNull('fecha_cierre')->orWhereBetween('fecha_cierre', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]))
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderByDesc('fecha_emision')
                ->limit(200)
                ->get()
                ->map(fn ($hr) => [
                    'id' => $hr->id,
                    'numero' => $hr->numero,
                    'fecha_cierre' => $hr->fecha_cierre,
                    'id_tractivo' => $hr->id_tractivo,
                    'id_arrastre' => $hr->id_arrastre,
                    'id_chofer' => $hr->id_chofer,
                    'id_chofer2' => $hr->id_chofer2,
                    'tractivo_codigo' => $hr->tractivo?->codigo,
                    'arrastre_codigo' => $hr->arrastre?->codigo,
                    'chofer_nombre' => $hr->chofer ? trim($hr->chofer->nombre.' '.$hr->chofer->apellidos) : null,
                    'chofer2_nombre' => $hr->chofer2 ? trim($hr->chofer2->nombre.' '.$hr->chofer2->apellidos) : null,
                    'id_cliente' => $hr->id_cliente,
                ]),
            'tractivos' => Tractivo::with('grupo:id,nombre')
                ->select('id', 'codigo', 'marca', 'modelo', 'placa', 'id_grupo')
                ->whereNull('fecha_baja')
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderBy('codigo')
                ->get()
                ->map(fn ($t) => ['id' => $t->id, 'codigo' => $t->codigo, 'tipo' => $t->grupo?->nombre, 'marca' => $t->marca, 'placa' => $t->placa]),
            'arrastres' => Tractivo::select('id', 'codigo', 'marca', 'placa')
                ->where('id_grupo', 8)
                ->whereNull('fecha_baja')
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->orderBy('codigo')
                ->get(),
            'choferes' => Bolsa::select('id', 'nombre', 'apellidos')
                ->where('activo', true)
                ->where('tiene_licencia', true)
                ->when($entidadId, fn ($q) => $q->where('id_entidad', $entidadId))
                ->where(fn ($q) => $q->whereNull('licencia_vencimiento')->orWhere('licencia_vencimiento', '>=', $hoy))
                ->orderBy('nombre')
                ->get()
                ->map(fn ($b) => ['id' => $b->id, 'nombre' => $b->nombre, 'apellidos' => $b->apellidos]),
        ];
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $validated['id_entidad'] = (int) session('entidad_activa_id');
        $validated['id_user'] = auth()->id();
        $validated['estado'] = 'pendiente';
        $validated['numero'] = $this->generarNumero($validated['fecha_solicitud']);

        SolicitudesServicio::create($validated);

        return redirect()->route('solicitudes.index')->with('success', 'Solicitud creada correctamente.');
    }

    public function update(Request $request, SolicitudesServicio $solicitude)
    {
        $validated = $this->validar($request, $solicitude);
        $solicitude->update($validated);

        return redirect()->route('solicitudes.index')->with('success', 'Solicitud actualizada correctamente.');
    }

    public function destroy(SolicitudesServicio $solicitude)
    {
        if ($solicitude->fecha_ejecutada) {
            return redirect()->route('solicitudes.index')->with('error', 'No se puede eliminar una solicitud ejecutada.');
        }

        $solicitude->delete();

        return redirect()->route('solicitudes.index')->with('success', 'Solicitud eliminada correctamente.');
    }

    /**
     * Duplica una solicitud con los mismos datos excepto el número, que se
     * regenera con el siguiente secuencial del año.
     */
    public function duplicar(SolicitudesServicio $solicitude)
    {
        $copia = SolicitudesServicio::create($solicitude->only([
            'id_entidad', 'id_cliente', 'id_lugar_origen', 'id_lugar_destino',
            'id_producto', 'id_producto2', 'id_tipo_carga', 'id_tipo_carga2',
            'id_moneda', 'id_user', 'fecha_solicitud', 'fecha_planificada',
            'valor_mt', 'valor_total', 'peso1', 'peso2', 'distancia', 'notas',
        ]) + [
            'numero' => $this->generarNumero($solicitude->fecha_solicitud?->toDateString() ?? now()->toDateString()),
            'estado' => 'pendiente',
            'fecha_ejecutada' => null,
        ]);

        return redirect()->route('solicitudes.index')->with('success', "Solicitud duplicada correctamente ({$copia->numero}).");
    }

    /**
     * Registra una carta de porte (giro) en base a la solicitud: acumula las
     * toneladas ejecutadas y recalcula estado y pendientes.
     */
    public function registrarCartaPorte(Request $request, SolicitudesServicio $solicitude)
    {
        $total = (float) ($solicitude->peso1 ?? 0) + (float) ($solicitude->peso2 ?? 0);
        $ejecutado = (float) CartaPorte::where('id_solicitud', $solicitude->id)->where('estado', '!=', 'cancelada')->sum('toneladas');
        $pendientes = max(0, $total - $ejecutado);

        $validated = $request->validate([
            'numero' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                $existe = CartaPorte::where('numero', trim((string) $value))
                    ->where('cancelada', false)
                    ->whereNull('deleted_at')
                    ->exists();
                if ($existe) {
                    $fail('El folio ya está registrado. Verifique antes de emitirlo.');
                }
            }],
            'ingreso_mt' => ['nullable', 'numeric', 'min:0', 'max:'.($pendientes > 0 ? $pendientes : 0.01)],
            'toneladas' => ['nullable', 'numeric', 'min:0'],
            'peso1' => ['nullable', 'numeric', 'min:0'],
            'peso2' => ['nullable', 'numeric', 'min:0'],
            'fecha_parte' => ['nullable', 'date'],
            'fecha_emision' => ['nullable', 'date'],
            'id_hoja_ruta' => ['nullable', 'exists:hojas_ruta,id'],
            'id_tractivo' => ['nullable', 'exists:tractivos,id'],
            'id_arrastre' => ['nullable', 'exists:tractivos,id'],
            'id_chofer' => ['nullable', 'exists:bolsa,id'],
            'id_chofer2' => ['nullable', 'exists:bolsa,id'],
            'id_lugar_origen' => ['nullable', 'exists:lugares,id'],
            'id_lugar_destino' => ['nullable', 'exists:lugares,id'],
            'id_producto' => ['nullable', 'exists:productos,id'],
            'id_producto2' => ['nullable', 'exists:productos,id'],
            'id_tipo_carga' => ['nullable', 'exists:tipos_cargas,id'],
            'id_tipo_carga2' => ['nullable', 'exists:tipos_cargas,id'],
            'distancia' => ['nullable', 'integer', 'min:0'],
            'conduce' => ['nullable', 'string', 'max:150'],
            'notas' => ['nullable', 'string', 'max:150'],
            'imprimir' => ['sometimes', 'boolean'],
        ]);

        $fechaEmision = $validated['fecha_emision'] ?? now()->toDateString();

        $carta = CartaPorte::create([
            'numero' => $validated['numero'],
            'id_hoja_ruta' => $validated['id_hoja_ruta'] ?? null,
            'id_solicitud' => $solicitude->id,
            'id_cliente' => $solicitude->id_cliente,
            'id_tractivo' => $validated['id_tractivo'] ?? null,
            'id_arrastre' => $validated['id_arrastre'] ?? null,
            'id_chofer' => $validated['id_chofer'] ?? null,
            'id_chofer2' => $validated['id_chofer2'] ?? null,
            'id_lugar_origen' => $validated['id_lugar_origen'] ?? $solicitude->id_lugar_origen,
            'id_lugar_destino' => $validated['id_lugar_destino'] ?? $solicitude->id_lugar_destino,
            'id_producto' => $validated['id_producto'] ?? $solicitude->id_producto,
            'id_producto2' => $validated['id_producto2'] ?? $solicitude->id_producto2,
            'id_tipo_carga' => $validated['id_tipo_carga'] ?? $solicitude->id_tipo_carga,
            'id_tipo_carga2' => $validated['id_tipo_carga2'] ?? $solicitude->id_tipo_carga2,
            'id_moneda' => $solicitude->id_moneda,
            'id_user' => auth()->id(),
            'fecha_emision' => $fechaEmision,
            'fecha_parte' => $validated['fecha_parte'] ?? $fechaEmision,
            'peso1' => $validated['peso1'] ?? $validated['toneladas'] ?? $validated['ingreso_mt'] ?? 0,
            'peso2' => $validated['peso2'] ?? 0,
            'toneladas' => $validated['toneladas'] ?? $validated['ingreso_mt'] ?? (float) ($validated['peso1'] ?? 0) + (float) ($validated['peso2'] ?? 0),
            'ingreso_mt' => $validated['ingreso_mt'] ?? (float) ($validated['peso1'] ?? 0) + (float) ($validated['peso2'] ?? 0),
            'distancia' => $validated['distancia'] ?? $solicitude->distancia,
            'flete_mt' => $solicitude->valor_mt,
            'conduce' => $validated['conduce'] ?? null,
            'notas' => $validated['notas'] ?? null,
            'imprimir' => $request->boolean('imprimir'),
            'estado' => 'emitida',
        ]);

        $estaRealizada = $total > 0
            && (float) CartaPorte::where('id_solicitud', $solicitude->id)->where('estado', '!=', 'cancelada')->sum('toneladas') >= $total;

        $solicitude->update([
            'fecha_ejecutada' => $estaRealizada ? now()->toDateString() : null,
            'estado' => $estaRealizada ? 'ejecutada' : 'en_proceso',
        ]);

        return redirect()->route('solicitudes.index')->with('success', "Carta de porte {$carta->numero} registrada correctamente.");
    }

    private function validar(Request $request, ?SolicitudesServicio $solicitude = null): array
    {
        $unique = $solicitude ? 'unique:solicitudes_servicio,numero,'.$solicitude->id : 'unique:solicitudes_servicio,numero';

        return $request->validate([
            'numero' => 'nullable|'.$unique.'|max:50',
            'fecha_solicitud' => 'required|date',
            'fecha_planificada' => 'nullable|date',
            'id_cliente' => 'required|exists:clientes,id',
            'id_lugar_origen' => 'nullable|exists:lugares,id',
            'id_lugar_destino' => 'nullable|exists:lugares,id',
            'id_producto' => 'nullable|exists:productos,id',
            'id_producto2' => 'nullable|exists:productos,id',
            'id_tipo_carga' => 'nullable|exists:tipos_cargas,id',
            'id_tipo_carga2' => 'nullable|exists:tipos_cargas,id',
            'peso1' => 'nullable|numeric|min:0',
            'peso2' => 'nullable|numeric|min:0',
            'distancia' => 'nullable|integer|min:0',
            'notas' => 'nullable|max:150',
            'valor_mt' => 'nullable|numeric|min:0',
            'valor_total' => 'nullable|numeric|min:0',
        ]);
    }

    private function generarNumero(string $fechaSolicitud): string
    {
        $anio = substr($fechaSolicitud, 0, 4);
        $base = 'SOL-'.$anio.'-';
        $ultimo = SolicitudesServicio::where('numero', 'like', $base.'%')
            ->orderBy('numero', 'desc')
            ->value('numero');
        $sec = $ultimo ? ((int) substr($ultimo, strlen($base))) + 1 : 1;

        return $base.str_pad((string) $sec, 5, '0', STR_PAD_LEFT);
    }
}