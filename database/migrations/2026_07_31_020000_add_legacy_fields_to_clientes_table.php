<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega a clientes los campos originales de com_clientes (legacy) que
 * faltaban en el esquema nuevo. Nombres originales conservados tal cual;
 * la depuración de los que no se usen queda para después (decisión del usuario).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->integer('nrocontrato')->nullable()->after('email');
            $table->date('falta')->nullable()->after('nrocontrato');
            $table->date('fvencimiento')->nullable()->after('falta');
            $table->string('codreup', 120)->nullable()->after('fvencimiento');
            $table->string('agenciamn', 100)->nullable()->after('codreup');
            $table->string('ctamn', 50)->nullable()->after('agenciamn');
            $table->unsignedBigInteger('idorganismos')->nullable()->after('ctamn');
            $table->unsignedBigInteger('idosdes')->nullable()->after('idorganismos');
            $table->unsignedBigInteger('idmonedas')->nullable()->after('idosdes');
            $table->unsignedBigInteger('idclientesel')->nullable()->after('idmonedas');
            $table->string('emailfacturacion', 200)->nullable()->after('idclientesel');
            $table->string('notas', 600)->nullable()->after('emailfacturacion');
            $table->integer('cancelado')->nullable()->after('notas');
            $table->integer('descuento')->nullable()->after('cancelado');
            $table->decimal('plan', 15, 2)->nullable()->after('descuento');
            $table->integer('mora')->nullable()->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'nrocontrato', 'falta', 'fvencimiento', 'codreup', 'agenciamn',
                'ctamn', 'idorganismos', 'idosdes', 'idmonedas', 'idclientesel',
                'emailfacturacion', 'notas', 'cancelado', 'descuento', 'plan', 'mora',
            ]);
        });
    }
};
