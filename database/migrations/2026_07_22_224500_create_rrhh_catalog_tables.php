<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_calificadores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_causas_laborales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_causas_baja', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->foreignId('id_tipo_causa_laboral')->nullable()->constrained('tipos_causas_laborales');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_causas_movimiento', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->foreignId('id_tipo_causa_laboral')->nullable()->constrained('tipos_causas_laborales');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_clasificacion_laboral', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->boolean('designado')->default(false);
            $table->boolean('cuadro')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_color_piel', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 50);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_deducciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->integer('clave')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_especialidad', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_estado_civil', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 50);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_grupo_horario', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_integracion_politica', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 100);
            $table->integer('politica')->nullable();
            $table->string('abreviatura', 10)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_medios_proteccion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_nivel_educacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->string('abreviatura', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_sexo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 50);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_tallas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('tipos_ubicacion_defensa', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('perfiles_rh', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 60);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tables = [
            'perfiles_rh',
            'tipos_ubicacion_defensa',
            'tipos_tallas',
            'tipos_sexo',
            'tipos_plantillas',
            'tipos_nivel_educacion',
            'tipos_medios_proteccion',
            'tipos_integracion_politica',
            'tipos_grupo_horario',
            'tipos_estado_civil',
            'tipos_especialidad',
            'tipos_deducciones',
            'tipos_color_piel',
            'tipos_clasificacion_laboral',
            'tipos_causas_movimiento',
            'tipos_causas_baja',
            'tipos_causas_laborales',
            'tipos_calificadores',
        ];
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
