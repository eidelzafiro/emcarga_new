<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migra la estructura de "Precios por acuerdo" (com_taracuerdos legacy) hacia la
 * tabla `acuerdos`, y añade a `lugares` los campos del formulario legacy
 * (direccion, personalidad).
 *
 * La tabla `acuerdos` tenía un esquema genérico (codigo/descripcion/fecha/tarifa_base)
 * que NO coincide con el concepto legacy de "precio por acuerdo"
 * (cliente × origen × destino × producto + tarifa + importe). Se reestructura
 * para reflejar esa forma exacta de com_taracuerdos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acuerdos', function (Blueprint $table) {
            $table->dropUnique('acuerdos_codigo_unique');
            $table->dropColumn(['codigo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'tarifa_base', 'moneda']);

            $table->foreignId('id_lugar_origen')->nullable()->after('id_cliente')->constrained('lugares')->nullOnDelete();
            $table->foreignId('id_lugar_destino')->nullable()->after('id_lugar_origen')->constrained('lugares')->nullOnDelete();
            $table->foreignId('id_producto')->nullable()->after('id_lugar_destino')->constrained('productos')->nullOnDelete();
            $table->decimal('tarifa_ton', 12, 2)->default(0)->after('id_producto');
            $table->decimal('importe', 12, 2)->default(0)->after('tarifa_ton');
            $table->unsignedBigInteger('id_entidad')->nullable()->after('importe');

            $table->unique(
                ['id_cliente', 'id_lugar_origen', 'id_lugar_destino', 'id_producto'],
                'acuerdos_cliente_ruta_producto_unique'
            );
        });

        Schema::table('lugares', function (Blueprint $table) {
            $table->string('direccion', 500)->nullable()->after('municipio');
            $table->string('personalidad', 255)->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('lugares', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'personalidad']);
        });

        Schema::table('acuerdos', function (Blueprint $table) {
            $table->dropUnique('acuerdos_cliente_ruta_producto_unique');

            $table->dropForeign(['id_producto']);
            $table->dropForeign(['id_lugar_destino']);
            $table->dropForeign(['id_lugar_origen']);
            $table->dropColumn(['id_lugar_origen', 'id_lugar_destino', 'id_producto', 'tarifa_ton', 'importe', 'id_entidad']);

            $table->string('codigo', 50)->nullable()->unique();
            $table->string('descripcion', 255);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->decimal('tarifa_base', 12, 2);
            $table->string('moneda', 3)->default('CUP');
        });
    }
};
