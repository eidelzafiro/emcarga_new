<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseña las tres tablas de combustible con paridad del legacy CI3:
 *
 *   cont_combcarga        → combustible_cargas      (cabecera de carga)
 *   cont_combdetallecarga → detalles_carga_combustible (detalle por tarjeta)
 *   cont_combdescarga     → combustible_descargas   (descarga vinculada a hoja de ruta)
 *
 * Las tablas estaban vacías (0 filas), por lo que se dropean y recrean
 * con el esquema definitivo (FK checks deshabilitados durante la operación).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Cabecera de carga (réplica cont_combcarga)
        Schema::dropIfExists('combustible_cargas');
        Schema::create('combustible_cargas', function (Blueprint $table) {
            $table->id();
            $table->date('fcarga');
            $table->decimal('saldocargado', 10, 2)->default(0);
            $table->decimal('saldoxtarjeta', 10, 2)->default(0);
            $table->foreignId('id_monedas')->nullable()->constrained('monedas');
            $table->foreignId('id_tipo_combustibles')->nullable()->constrained('tipos_combustibles');
            $table->foreignId('id_responsable')->nullable()->constrained('bolsa');
            $table->string('folio', 20);
            $table->text('notas')->nullable();
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->string('estado', 50)->default('registrada');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fcarga');
            $table->index('estado');
        });

        // Detalle de carga por tarjeta (réplica cont_combdetallecarga)
        Schema::dropIfExists('detalles_carga_combustible');
        Schema::create('detalles_carga_combustible', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carga')->constrained('combustible_cargas');
            $table->foreignId('id_tarjeta')->constrained('tarjetas');
            $table->date('fcarga');
            $table->string('folio', 10);
            $table->decimal('saldo_mon', 10, 2)->default(0);
            $table->decimal('saldo_lts', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['id_carga', 'id_tarjeta']);
        });

        // Descarga vinculada a hoja de ruta (réplica cont_combdescarga)
        Schema::dropIfExists('combustible_descargas');
        Schema::create('combustible_descargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tarjeta')->constrained('tarjetas');
            $table->date('fdescarga');
            $table->string('folio', 10);
            $table->decimal('saldo_mon', 10, 2)->default(0);
            $table->decimal('saldo_lts', 10, 2)->default(0);
            $table->foreignId('id_hoja_ruta')->constrained('hojas_ruta');
            $table->unsignedBigInteger('id_comprobante')->nullable();
            $table->string('hora_descarga', 10)->nullable();
            $table->foreignId('id_servicentro')->nullable()->constrained('servicentros');
            $table->date('f_chip')->nullable();
            $table->decimal('kms', 6, 2)->default(0);
            $table->foreignId('id_entidad')->nullable()->constrained('entidades');
            $table->string('estado', 50)->default('registrada');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('fdescarga');
            $table->index(['id_hoja_ruta', 'id_tarjeta']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // Combustible descargas (esquema anterior)
        Schema::dropIfExists('combustible_descargas');
        Schema::create('combustible_descargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carga')->nullable()->constrained('combustible_cargas');
            $table->foreignId('id_tractivo')->constrained('tractivos');
            $table->date('fecha_descarga');
            $table->decimal('cantidad_litros', 10, 2);
            $table->decimal('kilometraje', 10, 2)->nullable();
            $table->string('tipo_combustible', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('registrada');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // Detalles de carga (esquema anterior)
        Schema::dropIfExists('detalles_carga_combustible');
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
        });

        // Cabecera de carga (esquema anterior)
        Schema::dropIfExists('combustible_cargas');
        Schema::create('combustible_cargas', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique();
            $table->foreignId('id_tarjeta')->nullable()->constrained('tarjetas');
            $table->foreignId('id_tractivo')->nullable()->constrained('tractivos');
            $table->foreignId('id_bolsa')->nullable()->constrained('bolsa');
            $table->date('fecha_carga');
            $table->decimal('cantidad_litros', 10, 2);
            $table->decimal('precio_litro', 10, 4);
            $table->decimal('total', 12, 2);
            $table->string('tipo_combustible', 50)->nullable();
            $table->string('lugar', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 50)->default('registrada');
            $table->foreignId('id_user')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }
};
