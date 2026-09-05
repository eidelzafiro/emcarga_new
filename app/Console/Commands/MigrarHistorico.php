<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarHistorico extends Command
{
    protected $signature = 'zafiro:migrar-historico
        {--anio-inicio=2017 : Año inicial del rango a migrar}
        {--anio-fin=2025 : Año final del rango a migrar}
        {--fase=todo : Fase a ejecutar (fase1, fase2, todo)}
        {--solo= : Ejecutar solo un método específico (servicentros, dietas, hr, cp, solicitudes, facturas, aforos, combustible, cierre-tarjetas)}
        {--dry-run : Solo mostrar lo que se haría, sin ejecutar}';

    protected $description = 'Migración histórica de datos del legacy EMCARGA (2017-2025) a la BD nueva Zafiro';

    protected array $fase1Methods = [
        'servicentros' => 'Migrar todos los servicentros del legacy (sin filtro de año)',
    ];

    protected array $fase2Methods = [
        'hr'               => 'Hojas de ruta (com_hojaruta)',
        'cp'               => 'Cartas de porte (com_girado)',
        'solicitudes'      => 'Solicitudes de servicio (com_solicitudes)',
        'facturas'         => 'Facturas (com_rfactura)',
        'aforos'           => 'Aforos + líneas + indicadores (com_aforo + com_girado + com_indicadores)',
        'combustible'      => 'Cargas + descargas de combustible',
        'cierre-tarjetas'  => 'Cierre de tarjetas (cont_htarjetas)',
    ];

    public function handle(): int
    {
        $anioInicio = (int) $this->option('anio-inicio');
        $anioFin = (int) $this->option('anio-fin');
        $fase = $this->option('fase');
        $solo = $this->option('solo');
        $dryRun = $this->option('dry-run');

        $this->newLine();
        $this->info('=== MIGRACIÓN HISTÓRICA EMCARGA → ZAFIRO ===');
        $this->info("Rango: {$anioInicio} → {$anioFin}");
        $this->info("Fase: {$fase}");
        if ($solo) {
            $this->info("Método específico: {$solo}");
        }
        if ($dryRun) {
            $this->warn('** MODO DRY-RUN: no se ejecutará nada **');
        }
        $this->newLine();

        $inicio = microtime(true);

        // ─── FASE 1: Completar datos faltantes ───
        if (in_array($fase, ['fase1', 'todo'])) {
            $this->info('─── FASE 1: Completar datos faltantes ───');

            if ($solo && $solo !== 'servicentros') {
                $this->warn("  Saltando fase 1 (solo ejecutando: {$solo})");
            } else {
                $this->migrarServicentrosFase1($dryRun);
            }
        }

        // ─── FASE 2: Histórico comercial ───
        if (in_array($fase, ['fase2', 'todo'])) {
            $this->newLine();
            $this->info('─── FASE 2: Migración histórica comercial ───');

            $this->migrarHistoricoComercial($anioInicio, $anioFin, $solo, $dryRun);
        }

        $total = microtime(true) - $inicio;
        $this->newLine();
        $this->info("=== COMPLETADO EN {$this->formatearTiempo($total)} ===");

        return self::SUCCESS;
    }

    /**
     * FASE 1: Migrar todos los servicentros del legacy (sin filtro de año).
     */
    private function migrarServicentrosFase1(bool $dryRun): void
    {
        $this->info('▶ Servicentros (todos los legacy)...');

        $legacy = DB::connection('legacy')->table('cont_servicentros');
        $total = $legacy->count();
        $nueva = DB::table('servicentros')->count();
        $faltan = $total - $nueva;

        $this->info("  Legacy: {$total} | Nueva: {$nueva} | Faltan: {$faltan}");

        if ($faltan <= 0) {
            $this->info('  ✓ Ya completo');
            return;
        }

        if ($dryRun) {
            $this->warn('  [DRY-RUN] Se insertarían ' . $faltan . ' servicentros');
            return;
        }

        $procesados = 0;
        $omitidos = 0;

        $legacy->orderBy('idservicentros')->chunk(500, function ($filas) use (&$procesados, &$omitidos) {
            foreach ($filas as $fila) {
                $idProvincia = $fila->idprovincias == 0 ? null : $fila->idprovincias;
                $nombre = trim($fila->servicentros ?? '');
                if ($nombre === '') {
                    $nombre = "SERVICENTRO #{$fila->idservicentros}";
                }

                try {
                    DB::table('servicentros')->updateOrInsert(
                        ['id' => $fila->idservicentros],
                        [
                            'id'           => $fila->idservicentros,
                            'nombre'       => $nombre,
                            'id_provincia' => $idProvincia,
                            'activo'       => true,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ]
                    );
                    $procesados++;
                } catch (\Throwable $e) {
                    $omitidos++;
                }
            }
        });

        $this->info("  ✓ Insertados: {$procesados} | Omitidos: {$omitidos}");
    }

    /**
     * FASE 2: Migrar histórico comercial año por año.
     */
    private function migrarHistoricoComercial(int $inicio, int $fin, ?string $solo, bool $dryRun): void
    {
        $etl = app(\App\Services\Etl\EtlService::class);

        for ($anio = $inicio; $anio <= $fin; $anio++) {
            $this->newLine();
            $this->info("--- AÑO {$anio} ---");

            if ($dryRun) {
                $this->migrarAnioDryRun($anio);
                continue;
            }

            $metodos = $this->obtenerMetodosParaAnio($solo);

            foreach ($metodos as $metodo => $descripcion) {
                $this->info("  ▶ {$descripcion}...");

                $inicioMetodo = microtime(true);
                try {
                    match ($metodo) {
                        'hr' => $etl->migrarHojasRuta(anio: $anio),
                        'cp' => $etl->migrarCartasPorte(anio: $anio),
                        'solicitudes' => $etl->migrarSolicitudes(anio: $anio),
                        'facturas' => $etl->migrarFacturas(anio: $anio),
                        'aforos' => $etl->migrarAforos(anio: $anio),
                        'dietas' => $etl->migrarDietas(anio: $anio),
                        'combustible' => $this->migrarCombustibleAnio($etl, $anio),
                        'cierre-tarjetas' => $etl->migrarCierreTarjetas(anio: $anio),
                        default => null,
                    };
                    $duracion = microtime(true) - $inicioMetodo;
                    $this->info("    ✓ OK ({$this->formatearTiempo($duracion)})");
                } catch (\Throwable $e) {
                    $this->error("    ✗ ERROR: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Ejecutar migración de combustible para un año.
     */
    private function migrarCombustibleAnio(\App\Services\Etl\EtlService $etl, int $anio): void
    {
        $etl->migrarCargasCombustible(anio: $anio);
        $etl->migrarDescargasCombustible(anio: $anio);
    }

    /**
     * Obtener los métodos a ejecutar según el filtro.
     */
    private function obtenerMetodosParaAnio(?string $solo): array
    {
        $todos = [
            'hr' => 'Hojas de ruta',
            'cp' => 'Cartas de porte',
            'solicitudes' => 'Solicitudes',
            'facturas' => 'Facturas',
            'aforos' => 'Aforos',
            'dietas' => 'Dietas',
            'combustible' => 'Combustible',
            'cierre-tarjetas' => 'Cierre tarjetas',
        ];

        if ($solo && isset($todos[$solo])) {
            return [$solo => $todos[$solo]];
        }

        return $todos;
    }

    /**
     * Mostrar resumen sin ejecutar (dry-run).
     */
    private function migrarAnioDryRun(int $anio): void
    {
        $legacy = DB::connection('legacy');

        $contadores = [
            'Hojas de ruta'      => $legacy->table('com_hojaruta')->whereYear('femision', $anio)->count(),
            'Cartas de porte'    => $legacy->table('com_girado')->whereYear('femision', $anio)->count(),
            'Solicitudes'        => $legacy->table('com_solicitudes')->whereYear('fsolicitud', $anio)->count(),
            'Facturas'           => $legacy->table('com_rfactura')->whereYear('ffactura', $anio)->count(),
            'Aforos'             => $legacy->table('com_aforo')->whereYear('fparte', $anio)->count(),
            'Dietas'             => $legacy->table('cont_dietas')->whereYear('fcostodietas', $anio)->count(),
            'Cargas combustible' => $legacy->table('cont_combcarga')->whereYear('fcarga', $anio)->count(),
            'Descargas combust.' => $legacy->table('cont_combdescarga')->whereYear('fdescarga', $anio)->count(),
            'Cierre tarjetas'    => $legacy->table('cont_htarjetas')->whereYear('ftrabajo', $anio)->count(),
        ];

        foreach ($contadores as $tabla => $count) {
            if ($count > 0) {
                $this->info("  {$tabla}: {$count} registros");
            }
        }
    }

    private function formatearTiempo(float $segundos): string
    {
        if ($segundos < 60) {
            return number_format($segundos, 1) . 's';
        }
        $min = floor($segundos / 60);
        $seg = $segundos % 60;
        return "{$min}m " . number_format($seg, 0) . 's';
    }
}
