<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paridad legacy de diferenciales: el legacy (tec_diferenciales) trae
 * finstalada, fbaja e idlubricantes; la tabla nueva no las tenía aunque el
 * modelo Diferenciale sí las declara en $fillable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diferenciales', function (Blueprint $table) {
            $table->date('fecha_instalacion')->nullable()->after('capacidad_carter');
            $table->date('fecha_baja')->nullable()->after('fecha_instalacion');
            $table->foreignId('id_lubricante')->nullable()->after('id_tractivo')
                ->constrained('lubricantes', 'id', 'fk_dif_lubricante')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('diferenciales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_lubricante');
            $table->dropColumn(['fecha_instalacion', 'fecha_baja']);
        });
    }
};
