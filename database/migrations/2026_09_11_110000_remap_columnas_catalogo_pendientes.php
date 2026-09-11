<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remapea columnas de negocio que quedaron con el id legacy (origen_id) en
 * lugar del id de catalogo_items tras la unificación de codificadores, y
 * blinda con FK las columnas ya correctas que carecían de constraint.
 *
 * La FK a catalogo_items(id) por sí sola no valida el tipo, por eso un id
 * legacy pequeño (14) podía "colar" apuntando a un ítem de otro tipo; el
 * remapeo por origen_id corrige la semántica.
 *
 * Idempotente: tras el remapeo los valores son ids de catálogo (>=3000) que
 * ya no coinciden con los origen_id legacy.
 */
return new class extends Migration
{
    /** [tabla, columna, tipo de catálogo] */
    private array $remaps = [
        ['combustible_cargas', 'id_tipo_combustibles', 'tipos_combustibles'],
        ['cierre_tarjetas', 'id_tipo_combustibles', 'tipos_combustibles'],
        ['neumaticos', 'id_posicion', 'posiciones_neumaticos'],
        ['baterias', 'id_destino', 'destinos_agregados'],
        ['baterias', 'id_motivo_baja', 'motivos_baja_bateria'],
    ];

    /** [tabla, columna] que deben referenciar catalogo_items(id) */
    private array $fks = [
        ['arrastres', 'id_color_primario'],
        ['arrastres', 'id_color_secundario'],
        ['areas', 'id_tipo_sistema_pago'],
        ['penalizaciones', 'id_pago_adicional'],
    ];

    public function up(): void
    {
        foreach ($this->remaps as [$tabla, $col, $tipo]) {
            DB::statement("
                UPDATE {$tabla} t
                JOIN catalogo_items ci
                  ON ci.tipo = '{$tipo}' AND ci.origen_id = t.{$col}
                SET t.{$col} = ci.id
                WHERE t.{$col} IS NOT NULL
            ");
        }

        // areas.id_tipo_sistema_pago era INT (con signo); catalogo_items.id es
        // BIGINT UNSIGNED. Se normaliza para poder referenciarlo.
        DB::statement('ALTER TABLE areas MODIFY id_tipo_sistema_pago BIGINT UNSIGNED NULL');

        foreach ($this->fks as [$tabla, $col]) {
            $this->agregarFk($tabla, $col);
        }
    }

    public function down(): void
    {
        foreach ($this->fks as [$tabla, $col]) {
            $nombre = "fk_{$tabla}_{$col}_catalogo";
            if ($this->fkExiste($tabla, $nombre)) {
                DB::statement("ALTER TABLE {$tabla} DROP FOREIGN KEY {$nombre}");
            }
        }
    }

    private function agregarFk(string $tabla, string $col): void
    {
        $nombre = "fk_{$tabla}_{$col}_catalogo";

        if ($this->fkExiste($tabla, $nombre)) {
            return;
        }

        DB::statement(
            "ALTER TABLE {$tabla} ADD CONSTRAINT {$nombre}
             FOREIGN KEY ({$col}) REFERENCES catalogo_items(id) ON DELETE SET NULL"
        );
    }

    private function fkExiste(string $tabla, string $nombre): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tabla)
            ->where('CONSTRAINT_NAME', $nombre)
            ->exists();
    }
};
