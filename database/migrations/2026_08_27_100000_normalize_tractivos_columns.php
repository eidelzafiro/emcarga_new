<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalización de tractivos (2026-08-27): elimina las columnas
     * desnormalizadas que son derivables de un FK o de una relación polimórfica:
     *  - marca, modelo            -> id_tipo_vehiculo (tipo_vehiculos)
     *  - color                    -> id_color_primario (catalogo_items)
     *  - numero_motor             -> id_motor (motores.codigo)
     *  - numero_caja              -> id_caja (cajas.codigo)
     *  - nro_carroceria/registro/resolucion -> vehiculos_documentacion
     *    (tabla polimórfica ya existente, donde viven los demás datos documentales).
     */
    public function up(): void
    {
        // 1) Ampliar la tabla de documentación con los 3 números.
        Schema::table('vehiculos_documentacion', function (Blueprint $table) {
            $table->string('nro_carroceria', 100)->nullable();
            $table->string('nro_registro', 100)->nullable();
            $table->string('nro_resolucion', 100)->nullable();
        });

        $ahora = now();

        // 2) Copiar los valores existentes hacia documentación.
        DB::update(
            "UPDATE vehiculos_documentacion d
             JOIN tractivos t ON d.vehiculo_type = 'tractivo' AND d.vehiculo_id = t.id
             SET d.nro_carroceria = t.nro_carroceria,
                 d.nro_registro = t.nro_registro,
                 d.nro_resolucion = t.nro_resolucion"
        );

        // 3) Crear filas de documentación para tractores que tengan alguno de
        //    estos números pero aún no tuvieran fila (red de seguridad).
        DB::insert(
            "INSERT INTO vehiculos_documentacion
                (vehiculo_type, vehiculo_id, nro_carroceria, nro_registro, nro_resolucion, created_at, updated_at)
             SELECT 'tractivo', t.id, t.nro_carroceria, t.nro_registro, t.nro_resolucion, ?, ?
             FROM tractivos t
             WHERE (t.nro_carroceria IS NOT NULL OR t.nro_registro IS NOT NULL OR t.nro_resolucion IS NOT NULL)
               AND NOT EXISTS (
                 SELECT 1 FROM vehiculos_documentacion d
                 WHERE d.vehiculo_type = 'tractivo' AND d.vehiculo_id = t.id
               )",
            [$ahora, $ahora]
        );

        // 4) Soltar las columnas redundantes de tractivos.
        Schema::table('tractivos', function (Blueprint $table) {
            $table->dropColumn([
                'marca', 'modelo', 'color',
                'numero_motor', 'numero_caja',
                'nro_carroceria', 'nro_registro', 'nro_resolucion',
            ]);
        });
    }

    public function down(): void
    {
        // Restaurar columnas en tractivos.
        Schema::table('tractivos', function (Blueprint $table) {
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('color', 100)->nullable();
            $table->string('numero_motor', 100)->nullable();
            $table->string('numero_caja', 100)->nullable();
            $table->string('nro_carroceria', 100)->nullable();
            $table->string('nro_registro', 100)->nullable();
            $table->string('nro_resolucion', 100)->nullable();
        });

        // Recuperar los nro_* desde documentación. marca/modelo/color/numero_motor/
        // numero_caja son derivables de FKs (id_tipo_vehiculo, id_color_*, id_motor,
        // id_caja) y no se persisten en documentación, por lo que en el rollback
        // quedan NULL (se re-derivan al reasignar los FKs).
        DB::update(
            "UPDATE tractivos t
             JOIN vehiculos_documentacion d ON d.vehiculo_type = 'tractivo' AND d.vehiculo_id = t.id
             SET t.nro_carroceria = d.nro_carroceria,
                 t.nro_registro = d.nro_registro,
                 t.nro_resolucion = d.nro_resolucion"
        );

        // Eliminar las columnas de documentación.
        Schema::table('vehiculos_documentacion', function (Blueprint $table) {
            $table->dropColumn(['nro_carroceria', 'nro_registro', 'nro_resolucion']);
        });
    }
};
