<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificadores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable()->unique();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('cargos', function (Blueprint $table) {
            $table->foreignId('id_calificador')->nullable()->after('id_entidad')
                ->constrained('calificadores')->nullOnDelete();

            $table->foreignId('id_fondo_tiempo')->nullable()->after('id_calificador')
                ->constrained('fondos_tiempo')->nullOnDelete();

            $table->foreignId('id_nivel_educacion')->nullable()->after('id_fondo_tiempo')
                ->constrained('tipos_nivel_educacion')->nullOnDelete();

            $table->foreignId('id_grupo_escala')->nullable()->after('id_nivel_educacion')
                ->constrained('grupos_escala')->nullOnDelete();

            $table->foreignId('id_clasificacion_laboral')->nullable()->after('id_grupo_escala')
                ->constrained('tipos_clasificacion_laboral')->nullOnDelete();

            $table->foreignId('id_categoria_cargo')->nullable()->after('id_clasificacion_laboral')
                ->constrained('categorias_cargo')->nullOnDelete();

            $table->foreignId('id_grupo_horario')->nullable()->after('id_categoria_cargo')
                ->constrained('tipos_grupo_horario')->nullOnDelete();

            $table->unsignedTinyInteger('tipo_salario')->default(1)->after('id_grupo_horario')
                ->comment('1=Sueldo, 0=Jornal');

            $table->unsignedTinyInteger('en_salario')->default(1)->after('tipo_salario')
                ->comment('1=Dias, 0=Horas');

            $table->decimal('tarifa', 20, 12)->nullable()->after('en_salario');
            $table->decimal('salario_escala', 10, 2)->nullable()->after('tarifa');
            $table->decimal('cla', 10, 4)->nullable()->after('salario_escala');
            $table->decimal('noct1', 10, 6)->nullable()->after('cla');
            $table->decimal('noct2', 10, 6)->nullable()->after('noct1');
            $table->decimal('pago_adicional', 10, 5)->nullable()->after('noct2');
            $table->boolean('aseo_tecnologico')->default(false)->after('pago_adicional');
        });
    }

    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropForeign(['id_calificador']);
            $table->dropForeign(['id_fondo_tiempo']);
            $table->dropForeign(['id_nivel_educacion']);
            $table->dropForeign(['id_grupo_escala']);
            $table->dropForeign(['id_clasificacion_laboral']);
            $table->dropForeign(['id_categoria_cargo']);
            $table->dropForeign(['id_grupo_horario']);

            $table->dropColumn([
                'id_calificador', 'id_fondo_tiempo', 'id_nivel_educacion',
                'id_grupo_escala', 'id_clasificacion_laboral', 'id_categoria_cargo',
                'id_grupo_horario', 'tipo_salario', 'en_salario', 'tarifa',
                'salario_escala', 'cla', 'noct1', 'noct2', 'pago_adicional',
                'aseo_tecnologico',
            ]);
        });

        Schema::dropIfExists('calificadores');
    }
};
