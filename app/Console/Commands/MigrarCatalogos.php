<?php

namespace App\Console\Commands;

use App\Models\CatalogoItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrarCatalogos extends Command
{
    protected $signature = 'zafiro:migrar-catalogos
        {--tipo= : Migrar solo un tipo específico (ej: tipos_cargas)}
        {--dry-run : Solo mostrar qué se migraría sin insertar}
        {--fresh : Vacía catalogo_items antes de migrar (re-sincronización total)}
        {--force : Migrar también tablas atípicas (tipos_cargas_reporte, tipos_medios_cargo, tipos_modelo, tipos_tractivos)}';

    protected $description = 'Migra datos de tablas tipos_* y tipo_* a catalogo_items';

    private array $excluidas = [
        'tipos_cargas_reporte',
        'tipos_medios_cargo',
        'tipos_modelo',
        'tipos_tractivos',
    ];

    private array $withDeletedAt = [
        'tipos_operaciones',
        'tipos_mantenimiento',
    ];

    private array $columnasBase = ['id', 'codigo', 'nombre', 'activo', 'deleted_at', 'created_at', 'updated_at'];

    public function handle(): int
    {
        $tipoFilter = $this->option('tipo');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $fresh = $this->option('fresh');

        if ($fresh && ! $dryRun) {
            if ($tipoFilter) {
                CatalogoItem::where('tipo', $tipoFilter)->forceDelete();
                $this->warn("Ítems de '{$tipoFilter}' eliminados (--fresh).");
            } else {
                CatalogoItem::withTrashed()->forceDelete();
                DB::statement('ALTER TABLE catalogo_items AUTO_INCREMENT = 1');
                $this->warn('catalogo_items vaciada (--fresh).');
            }
        }

        $tablas = $this->getTablasTipos();

        if ($tipoFilter) {
            $tablas = array_filter($tablas, fn($t) => $t === $tipoFilter);
            if (empty($tablas)) {
                $this->error("No se encontró la tabla '{$tipoFilter}'");
                return Command::FAILURE;
            }
        }

        $totalMigrados = 0;
        $totalOmitidos = 0;

        foreach ($tablas as $tabla) {
            if (in_array($tabla, $this->excluidas) && !$force) {
                $this->warn("  [OMITIDA] {$tabla} (excluida del catálogo unificado)");
                $totalOmitidos++;
                continue;
            }

            if (!Schema::hasTable($tabla)) {
                $this->warn("  [SALTADA] {$tabla} (no existe)");
                continue;
            }

            $columnas = Schema::getColumnListing($tabla);
            $columnasExtra = array_diff($columnas, $this->columnasBase);

            $registros = DB::table($tabla)->get();
            $count = $registros->count();

            if ($count === 0) {
                $this->line("  [VACÍA] {$tabla}");
                continue;
            }

            $this->line("  [MIGRANDO] {$tabla} ({$count} registros, extras: " . implode(', ', $columnasExtra ?: ['ninguna']) . ")");

            if ($dryRun) {
                $totalMigrados += $count;
                continue;
            }

            $bar = $this->output->createProgressBar($count);
            $bar->start();

            foreach ($registros as $row) {
                $extra = [];
                foreach ($columnasExtra as $col) {
                    if (in_array($col, ['deleted_at', 'created_at', 'updated_at'])) {
                        continue;
                    }
                    if ($row->$col !== null && $row->$col !== '') {
                        $extra[$col] = $this->castExtra($row->$col);
                    }
                }

                // Upsert idempotente por tipo + origen_id: re-ejecutar
                // actualiza en vez de duplicar, y conserva la trazabilidad
                // con la tabla tipos_* de origen.
                CatalogoItem::withTrashed()->updateOrCreate(
                    ['tipo' => $tabla, 'origen_id' => $row->id],
                    [
                        'codigo' => $row->codigo ?? null,
                        'nombre' => $row->nombre ?? '',
                        'activo' => $row->activo ?? true,
                        'extra' => empty($extra) ? null : $extra,
                        'deleted_at' => in_array($tabla, $this->withDeletedAt) ? ($row->deleted_at ?? null) : null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]
                );

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $totalMigrados += $count;
        }

        $this->newLine(2);

        if ($dryRun) {
            $this->info("=== DRY RUN: {$totalMigrados} registros listos para migrar, {$totalOmitidos} omitidos ===");
        } else {
            $this->info("=== Migración completada: {$totalMigrados} registros migrados, {$totalOmitidos} omitidos ===");
        }

        return Command::SUCCESS;
    }

    private function getTablasTipos(): array
    {
        $dbName = DB::connection()->getDatabaseName();
        $tablas = DB::select("
            SELECT TABLE_NAME FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
              AND (TABLE_NAME LIKE 'tipos\\_%' OR TABLE_NAME = 'tipo_ingresos')
            ORDER BY TABLE_NAME
        ", [$dbName]);

        return array_map(fn($t) => $t->TABLE_NAME, $tablas);
    }

    private function castExtra(mixed $value): mixed
    {
        if (is_numeric($value) && str_contains((string) $value, '.')) {
            return (float) $value;
        }
        if (is_numeric($value) && !str_contains((string) $value, '.')) {
            $int = (int) $value;
            return (string) $int === (string) $value ? $int : $value;
        }
        return $value;
    }
}
