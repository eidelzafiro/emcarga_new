<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índices compuestos para los patrones de consulta de la API móvil v1:
 * siempre filtran por `id_entidad` y, según el módulo, ordenan/agrupan por
 * otra columna (codigo, nombre, fecha, estado, mes/ano).
 *
 * Idempotente: solo crea el índice si no existe (por nombre), por lo que es
 * seguro re-ejecutarla sobre una BD con los índices ya presentes.
 */
return new class extends Migration
{
    /** @var array<string, array{0:string,1:array<int,string>}> */
    private array $indices = [
        'api_tractivos_entidad_codigo' => ['tractivos', ['id_entidad', 'codigo']],
        'api_bolsa_entidad_area' => ['bolsa', ['id_entidad', 'id_area']],
        'api_clientes_entidad_nombre' => ['clientes', ['id_entidad', 'nombre']],
        'api_tarjetas_entidad_numero' => ['tarjetas', ['id_entidad', 'numero']],
        'api_descargas_entidad_fecha' => ['combustible_descargas', ['id_entidad', 'fdescarga']],
        'api_ordenes_entidad_estado' => ['ordenes_taller', ['id_entidad', 'estado']],
        'api_salarios_entidad_mes_ano' => ['salarios', ['id_entidad', 'mes', 'ano']],
    ];

    public function up(): void
    {
        foreach ($this->indices as $nombre => [$tabla, $columnas]) {
            if (! Schema::hasTable($tabla) || $this->existeIndice($tabla, $nombre)) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) use ($columnas, $nombre) {
                $table->index($columnas, $nombre);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indices as $nombre => [$tabla, $columnas]) {
            if (! Schema::hasTable($tabla) || ! $this->existeIndice($tabla, $nombre)) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) use ($nombre) {
                $table->dropIndex($nombre);
            });
        }
    }

    private function existeIndice(string $tabla, string $nombre): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $tabla)
            ->where('index_name', $nombre)
            ->exists();
    }
};
