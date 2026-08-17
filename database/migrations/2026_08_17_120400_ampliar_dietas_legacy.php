<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía la tabla `dietas` (diseño genérico ya existente) con los campos del
 * legacy `cont_dietas` necesarios para el ETL y la paridad de contenido:
 * folio, anticipo, alimentos, hospedaje, otros, cancelada, moneda, tractivo,
 * reembolso y fechas de anticipo/liquidación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dietas', function (Blueprint $table) {
            $table->string('folio', 10)->nullable()->after('id_hoja_ruta');
            $table->decimal('anticipo', 10, 2)->default(0)->after('monto');
            $table->date('f_anticipo')->nullable()->after('anticipo');
            $table->decimal('alimentos', 10, 2)->default(0)->after('f_anticipo');
            $table->decimal('hospedaje', 10, 2)->default(0)->after('alimentos');
            $table->decimal('otros', 10, 2)->default(0)->after('hospedaje');
            $table->foreignId('id_monedas')->nullable()->after('otros')->constrained('monedas');
            $table->foreignId('id_tractivo')->nullable()->after('id_monedas')->constrained('tractivos');
            $table->foreignId('id_reembolso')->nullable()->after('id_tractivo')->constrained('reembolsos');
            $table->date('f_liquidacion')->nullable()->after('id_reembolso');
            $table->integer('folio_caja')->nullable()->after('f_liquidacion');
            $table->boolean('cancelada')->default(false)->after('folio_caja');
            $table->foreignId('id_entidad')->nullable()->after('cancelada')->constrained('entidades');
        });
    }

    public function down(): void
    {
        Schema::table('dietas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_entidad');
            $table->dropColumn('cancelada');
            $table->dropColumn('folio_caja');
            $table->dropColumn('f_liquidacion');
            $table->dropConstrainedForeignId('id_reembolso');
            $table->dropConstrainedForeignId('id_tractivo');
            $table->dropConstrainedForeignId('id_monedas');
            $table->dropColumn('otros');
            $table->dropColumn('hospedaje');
            $table->dropColumn('alimentos');
            $table->dropColumn('f_anticipo');
            $table->dropColumn('anticipo');
            $table->dropColumn('folio');
        });
    }
};
