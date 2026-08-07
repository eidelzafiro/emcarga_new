<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            // Datos generales
            $table->string('direccion', 200)->nullable()->after('abreviatura');
            $table->foreignId('id_provincia')->nullable()->constrained('provincias')->nullOnDelete()->after('direccion');
            $table->foreignId('id_municipio')->nullable()->constrained('municipios')->nullOnDelete()->after('id_provincia');
            $table->string('email', 200)->nullable()->after('id_municipio');
            $table->string('nit', 150)->nullable()->after('email');
            $table->string('licencia', 100)->nullable()->after('nit');

            // Cuentas bancarias
            $table->string('cta_unica', 150)->nullable()->after('licencia');
            $table->string('cta_mn', 150)->nullable()->after('cta_unica');
            $table->string('cta_me', 150)->nullable()->after('cta_mn');
            $table->string('agencia', 250)->nullable()->after('cta_me');

            // Configuración operativa
            $table->unsignedInteger('minutos')->nullable()->after('agencia');
            $table->unsignedInteger('folio_fact')->nullable()->after('minutos');
            $table->decimal('almacenaje', 6, 4)->nullable()->after('folio_fact');
            $table->integer('interruptos')->nullable()->after('almacenaje');
            $table->integer('lugares')->nullable()->after('interruptos');
            $table->integer('pass_dias')->default(120)->after('lugares');
            $table->integer('pass_cant_h')->default(2)->after('pass_dias');
            $table->text('notas_fact')->nullable()->after('pass_cant_h');

            // Mora
            $table->integer('mora_dias')->nullable()->after('notas_fact');
            $table->integer('mora_porciento')->nullable()->after('mora_dias');

            // Clientes / Finanzas
            $table->string('cliente_fincimex_mn', 20)->nullable()->after('mora_porciento');
            $table->string('talon_versat', 10)->nullable()->after('cliente_fincimex_mn');

            // Vida útil componentes
            $table->integer('vida_bateria')->nullable()->after('talon_versat');
            $table->integer('vida_neum_nuevo')->nullable()->after('vida_bateria');
            $table->integer('vida_neum_rec')->nullable()->after('vida_neum_nuevo');
            $table->integer('vida_neum_admin')->nullable()->after('vida_neum_rec');

            // Flags y config
            $table->boolean('disponible')->default(false)->after('vida_neum_admin');
            $table->boolean('desactivar_disp')->default(false)->after('disponible');
            $table->boolean('alertas_mtto')->default(false)->after('desactivar_disp');
            $table->integer('tipo_planificacion')->nullable()->after('alertas_mtto');
            $table->unsignedInteger('matriz')->nullable()->after('tipo_planificacion');
            $table->unsignedInteger('tasas_aforo')->nullable()->after('matriz');
            $table->unsignedInteger('requisitos')->nullable()->after('tasas_aforo');
            $table->unsignedInteger('oper_carga')->nullable()->after('requisitos');
            $table->unsignedInteger('descargas')->nullable()->after('oper_carga');
            $table->unsignedInteger('id_frecuencia')->nullable()->after('descargas');
            $table->unsignedInteger('id_sistema')->nullable()->after('id_frecuencia');
            $table->unsignedInteger('id_cajera')->nullable()->after('id_sistema');
            $table->unsignedInteger('id_parqueo')->nullable()->after('id_cajera');
        });
    }

    public function down(): void
    {
        Schema::table('entidades', function (Blueprint $table) {
            $cols = [
                'direccion', 'id_provincia', 'id_municipio', 'email', 'nit', 'licencia',
                'cta_unica', 'cta_mn', 'cta_me', 'agencia',
                'minutos', 'folio_fact', 'almacenaje', 'interruptos', 'lugares',
                'pass_dias', 'pass_cant_h', 'notas_fact',
                'mora_dias', 'mora_porciento', 'cliente_fincimex_mn', 'talon_versat',
                'vida_bateria', 'vida_neum_nuevo', 'vida_neum_rec', 'vida_neum_admin',
                'disponible', 'desactivar_disp', 'alertas_mtto', 'tipo_planificacion',
                'matriz', 'tasas_aforo', 'requisitos', 'oper_carga', 'descargas',
                'id_frecuencia', 'id_sistema', 'id_cajera', 'id_parqueo',
            ];
            $table->dropColumn($cols);
        });
    }
};
