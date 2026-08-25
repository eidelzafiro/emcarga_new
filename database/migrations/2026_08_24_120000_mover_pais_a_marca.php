<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLAS = ['tipos_tractivos', 'tipos_arrastres', 'otros_agregados', 'tarjetero'];

    public function up(): void
    {
        // 1. El país vive en la marca (catalogo_items).
        Schema::table('catalogo_items', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pais')->nullable()->after('nombre');
            $table->foreign('id_pais')->references('id')->on('catalogo_items')->nullOnDelete();
            $table->index('id_pais');
        });

        // 2. Migrar el país hacia la marca (primer no-nulo por marca).
        $db = DB::connection()->getDatabaseName();
        foreach (self::TABLAS as $tabla) {
            $filas = DB::table($tabla)
                ->whereNotNull('id_pais')
                ->whereNotNull('id_marca')
                ->select('id_marca', 'id_pais')
                ->get();

            foreach ($filas as $fila) {
                DB::table('catalogo_items')
                    ->where('id', $fila->id_marca)
                    ->whereNull('id_pais')
                    ->update(['id_pais' => $fila->id_pais]);
            }
        }

        // 3. Soltar id_pais de las tablas que lo duplicaban.
        foreach (self::TABLAS as $tabla) {
            $fk = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', $db)
                ->where('TABLE_NAME', $tabla)
                ->where('COLUMN_NAME', 'id_pais')
                ->whereNotNull('CONSTRAINT_NAME')
                ->value('CONSTRAINT_NAME');

            Schema::table($tabla, function (Blueprint $table) use ($fk) {
                if ($fk) {
                    $table->dropForeign($fk);
                }
                $table->dropColumn('id_pais');
            });
        }
    }

    public function down(): void
    {
        $db = DB::connection()->getDatabaseName();

        // Revertir: cada fila recibe el país de su marca.
        foreach (self::TABLAS as $tabla) {
            DB::statement("
                UPDATE {$tabla} t
                JOIN catalogo_items m ON m.id = t.id_marca
                SET t.id_pais = m.id_pais
                WHERE t.id_marca IS NOT NULL
            ");

            Schema::table($tabla, function (Blueprint $table) {
                $table->unsignedBigInteger('id_pais')->nullable();
                $table->foreign('id_pais')->references('id')->on('catalogo_items')->nullOnDelete();
            });
        }

        Schema::table('catalogo_items', function (Blueprint $table) use ($db) {
            $fk = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', $db)
                ->where('TABLE_NAME', 'catalogo_items')
                ->where('COLUMN_NAME', 'id_pais')
                ->whereNotNull('CONSTRAINT_NAME')
                ->value('CONSTRAINT_NAME');

            if ($fk) {
                $table->dropForeign($fk);
            }
            $table->dropColumn('id_pais');
        });
    }
};
