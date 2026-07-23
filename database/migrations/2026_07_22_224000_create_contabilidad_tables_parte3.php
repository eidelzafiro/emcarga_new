<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicentros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('codigo', 50)->nullable();
            $table->string('ubicacion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nombre');
        });

        Schema::create('tipos_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('firmas_autorizadas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('cargo', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('reportes_costos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_reporte');
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->decimal('combustible_mn', 12, 2)->default(0);
            $table->decimal('lubricante_mn', 12, 2)->default(0);
            $table->decimal('piezas_mn', 12, 2)->default(0);
            $table->decimal('salario', 12, 2)->default(0);
            $table->decimal('vacaciones', 12, 2)->default(0);
            $table->decimal('impuesto1', 12, 2)->default(0);
            $table->decimal('impuesto2', 12, 2)->default(0);
            $table->decimal('salario_total', 12, 2)->default(0);
            $table->decimal('dietas', 12, 2)->default(0);
            $table->decimal('amortizacion_mn', 12, 2)->default(0);
            $table->decimal('chapa', 12, 2)->default(0);
            $table->decimal('otros_gastos_mn', 12, 2)->default(0);
            $table->decimal('indirectos_admin_mn', 12, 2)->default(0);
            $table->decimal('indirectos_taller_mn', 12, 2)->default(0);
            $table->decimal('indirectos_mn', 12, 2)->default(0);
            $table->decimal('gastos_mn', 12, 2)->default(0);
            $table->decimal('ingresos_mn', 12, 2)->default(0);
            $table->decimal('kms_total', 10, 2)->default(0);
            $table->decimal('toneladas', 10, 2)->default(0);
            $table->decimal('trafico', 10, 2)->default(0);
            $table->integer('horas_taller')->default(0);
            $table->decimal('utilidad_mn', 12, 2)->default(0);
            $table->decimal('utilidad_mlc', 12, 2)->default(0);
            $table->decimal('costo_mn', 12, 4)->default(0);
            $table->decimal('costo_mlc', 12, 4)->default(0);
            $table->decimal('costo_tn_kms', 12, 4)->default(0);
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('borrador');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha_reporte');
            $table->index('id_tractivo');
        });

        Schema::create('estados_tarjetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjeta')->constrained('tarjetas');
            $table->date('fecha_movimiento');
            $table->foreignId('id_entrega')->nullable()->constrained('users');
            $table->foreignId('id_recibe')->nullable()->constrained('users');
            $table->decimal('saldo_mn', 12, 2)->default(0);
            $table->decimal('saldo_mlc', 12, 2)->default(0);
            $table->string('comprobante', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('id_tarjeta');
            $table->index('fecha_movimiento');
        });

        Schema::create('detalles_carga_combustible', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carga')->constrained('combustible_cargas');
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_bolsa')->nullable()->constrained('bolsa');
            $table->date('fecha_movimiento');
            $table->string('comprobante', 50)->nullable();
            $table->decimal('importe_mn', 12, 2)->default(0);
            $table->decimal('importe_mlc', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('id_carga');
            $table->index('fecha_movimiento');
        });

        Schema::create('combustibles_lubricantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carga')->nullable()->constrained('combustible_cargas');
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->foreignId('id_tipo_lubricante')->nullable()->constrained('tipos_lubricantes');
            $table->foreignId('id_causa')->nullable()->constrained('tipos_causas');
            $table->date('fecha');
            $table->string('folio', 50);
            $table->decimal('cantidad', 10, 2);
            $table->decimal('importe_mn', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('id_tractivo');
            $table->index('fecha');
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_documento')->nullable()->constrained('tipos_documentos');
            $table->foreignId('id_moneda')->nullable()->constrained('monedas');
            $table->date('fecha_pago');
            $table->string('numero_documento', 100)->nullable();
            $table->decimal('monto', 12, 2)->default(0);
            $table->string('concepto', 255)->nullable();
            $table->string('estado', 50)->default('pendiente');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fecha_pago');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('combustibles_lubricantes');
        Schema::dropIfExists('detalles_carga_combustible');
        Schema::dropIfExists('estados_tarjetas');
        Schema::dropIfExists('reportes_costos');
        Schema::dropIfExists('firmas_autorizadas');
        Schema::dropIfExists('tipos_documentos');
        Schema::dropIfExists('servicentros');
    }
};
