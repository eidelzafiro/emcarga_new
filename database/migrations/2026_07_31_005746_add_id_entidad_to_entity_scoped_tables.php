<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'firmas_autorizadas',
        'tractivos',
        'motores',
        'cajas',
        'diferenciales',
        'baterias',
        'neumaticos',
        'naves',
        'clientes',
        'solicitudes_servicio',
        'prefacturas',
        'facturas',
        'conciliaciones',
        'salarios',
        'areas',
        'cargos',
        'tipos_tasas',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreignId('id_entidad')
                    ->nullable()
                    ->constrained('entidades')
                    ->nullOnDelete()
                    ->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('id_entidad');
            });
        }
    }
};
