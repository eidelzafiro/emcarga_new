<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda el tipo de carga y la distancia de cada línea de tarifa (1-5) para
     * poder recargar el formulario de edición de un aforo. El legacy guarda
     * idtipocarga1-5 y distancia1-5 en com_girado; aquí se replican en aforos
     * para reconstruir las líneas al editar.
     */
    public function up(): void
    {
        Schema::table('aforos', function (Blueprint $table) {
            $table->foreignId('id_tipo_carga_1')->nullable()->after('tipo_indicadores');
            $table->foreignId('id_tipo_carga_2')->nullable()->after('id_tipo_carga_1');
            $table->foreignId('id_tipo_carga_3')->nullable()->after('id_tipo_carga_2');
            $table->foreignId('id_tipo_carga_4')->nullable()->after('id_tipo_carga_3');
            $table->foreignId('id_tipo_carga_5')->nullable()->after('id_tipo_carga_4');
            $table->integer('distancia_1')->default(0)->after('id_tipo_carga_5');
            $table->integer('distancia_2')->default(0)->after('distancia_1');
            $table->integer('distancia_3')->default(0)->after('distancia_2');
            $table->integer('distancia_4')->default(0)->after('distancia_3');
            $table->integer('distancia_5')->default(0)->after('distancia_4');
        });
    }

    public function down(): void
    {
        Schema::table('aforos', function (Blueprint $table) {
            $table->dropColumn([
                'id_tipo_carga_1', 'id_tipo_carga_2', 'id_tipo_carga_3', 'id_tipo_carga_4', 'id_tipo_carga_5',
                'distancia_1', 'distancia_2', 'distancia_3', 'distancia_4', 'distancia_5',
            ]);
        });
    }
};
