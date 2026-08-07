<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bolsa', function (Blueprint $table) {
            $table->string('color_piel', 50)->nullable()->after('sexo');
            $table->string('nivel_educacional', 100)->nullable()->after('color_piel');
            $table->string('estado_civil', 50)->nullable()->after('nivel_educacional');
            $table->string('ubicacion_defensa', 200)->nullable()->after('estado_civil');

            // Licencia de conducción
            $table->boolean('tiene_licencia')->default(false)->after('ubicacion_defensa');
            $table->string('categorias_licencia', 100)->nullable()->after('tiene_licencia');
            $table->text('limitaciones')->nullable()->after('categorias_licencia');

            // Chequeos médicos (solo choferes de transportación)
            $table->date('chequeo_medico_emision')->nullable()->after('limitaciones');
            $table->date('chequeo_medico_vencimiento')->nullable()->after('chequeo_medico_emision');
            $table->date('reubicacion_emision')->nullable()->after('chequeo_medico_vencimiento');
            $table->date('reubicacion_vencimiento')->nullable()->after('reubicacion_emision');
            $table->date('psicometrico_emision')->nullable()->after('reubicacion_vencimiento');
            $table->date('psicometrico_vencimiento')->nullable()->after('psicometrico_emision');
        });
    }

    public function down(): void
    {
        Schema::table('bolsa', function (Blueprint $table) {
            $table->dropColumn([
                'color_piel',
                'nivel_educacional',
                'estado_civil',
                'ubicacion_defensa',
                'tiene_licencia',
                'categorias_licencia',
                'limitaciones',
                'chequeo_medico_emision',
                'chequeo_medico_vencimiento',
                'reubicacion_emision',
                'reubicacion_vencimiento',
                'psicometrico_emision',
                'psicometrico_vencimiento',
            ]);
        });
    }
};
