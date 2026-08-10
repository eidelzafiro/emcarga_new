<?php

namespace App\Http\Controllers;

use App\Models\CartaPorte;
use App\Models\Cliente;
use App\Models\Lugare;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\SolicitudesServicio;
use App\Models\TipoCarga;
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

        // Seguimiento de toneladas: suma de cartas_porte (vigentes)
        foreach ($solicitudes as $sol) {
            $total = (float) ($sol->peso1 ?? 0) + (float) ($sol->peso2 ?? 0);
            $ejecutado = (float) $sol->cartasPorte->sum('ingreso_mt');
            $sol->toneladas_total = $total;
            $sol->toneladas_ejecutadas = $ejecutado;
            $sol->toneladas_pendientes = max(0, $total - $ejecutado);
            $sol->estado_cumplimiento = match (true) {
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
        ]);
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
        $pendientes = max(0, (float) ($solicitude->peso1 ?? 0) + (float) ($solicitude->peso2 ?? 0) - (float) CartaPorte::where('id_solicitud', $solicitude->id)->where('estado', '!=', 'cancelada')->sum('ingreso_mt'));

        $validated = $request->validate([
            'ingreso_mt' => ['required', 'numeric', 'min:0.01', 'max:'.($pendientes > 0 ? $pendientes : 0.01)],
            'fecha_parte' => ['nullable', 'date'],
        ]);

        $numero = $this->generarNumeroCartaPorte();

        CartaPorte::create([
            'numero' => $numero,
            'id_solicitud' => $solicitude->id,
            'id_cliente' => $solicitude->id_cliente,
            'id_lugar_origen' => $solicitude->id_lugar_origen,
            'id_lugar_destino' => $solicitude->id_lugar_destino,
            'id_producto' => $solicitude->id_producto,
            'id_producto2' => $solicitude->id_producto2,
            'id_tipo_carga' => $solicitude->id_tipo_carga,
            'id_tipo_carga2' => $solicitude->id_tipo_carga2,
            'id_moneda' => $solicitude->id_moneda,
            'id_user' => auth()->id(),
            'fecha_emision' => $validated['fecha_parte'] ?? now()->toDateString(),
            'fecha_parte' => $validated['fecha_parte'] ?? now()->toDateString(),
            'peso1' => $validated['ingreso_mt'],
            'toneladas' => $validated['ingreso_mt'],
            'ingreso_mt' => $validated['ingreso_mt'],
            'flete_mt' => $solicitude->valor_mt,
            'estado' => 'emitida',
        ]);

        $estaRealizada = (float) CartaPorte::where('id_solicitud', $solicitude->id)->where('estado', '!=', 'cancelada')->sum('ingreso_mt')
            >= (float) ($solicitude->peso1 ?? 0) + (float) ($solicitude->peso2 ?? 0);

        $solicitude->update([
            'fecha_ejecutada' => $estaRealizada ? now()->toDateString() : null,
            'estado' => $estaRealizada ? 'ejecutada' : 'en_proceso',
        ]);

        return redirect()->route('solicitudes.index')->with('success', "Carta de porte {$numero} registrada correctamente.");
    }

    private function generarNumeroCartaPorte(): string
    {
        $base = 'CP-'.now()->format('Y').'-';
        $ultimo = CartaPorte::where('numero', 'like', $base.'%')
            ->orderBy('numero', 'desc')
            ->value('numero');
        $sec = $ultimo ? ((int) substr($ultimo, strlen($base))) + 1 : 1;

        return $base.str_pad((string) $sec, 5, '0', STR_PAD_LEFT);
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