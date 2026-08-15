<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D3 (auditoría): índices para las consultas reales y limpieza de índices
 * redundantes.
 *
 * - aforos: índice compuesto (fecha_parte, id_factura) para el grid por mes+factura.
 * - cartas_porte: índice compuesto (id_solicitud, estado) para seguimiento de solicitudes.
 * - facturas: índice UNIQUE (numero) para proteger el correlativo {anio}00001+.
 * - cartas_porte: se elimina el índice no-único `numero` (duplicado del unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aforos', function (Blueprint $table) {
            if (! $this->indexExiste('aforos', 'aforos_fecha_estado_index')) {
                $table->index(['fecha_parte', 'id_factura'], 'aforos_fecha_estado_index');
            }
        });

        Schema::table('cartas_porte', function (Blueprint $table) {
            if (! $this->indexExiste('cartas_porte', 'cartas_solicitud_estado_index')) {
                $table->index(['id_solicitud', 'estado'], 'cartas_solicitud_estado_index');
            }
            if ($this->indexExiste('cartas_porte', 'cartas_porte_numero_index')) {
                $table->dropIndex('cartas_porte_numero_index');
            }
        });

        Schema::table('facturas', function (Blueprint $table) {
            if (! $this->indexExiste('facturas', 'facturas_numero_unique')) {
                $table->unique('numero', 'facturas_numero_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('aforos', function (Blueprint $table) {
            if ($this->indexExiste('aforos', 'aforos_fecha_estado_index')) {
                $table->dropIndex('aforos_fecha_estado_index');
            }
        });

        Schema::table('cartas_porte', function (Blueprint $table) {
            if ($this->indexExiste('cartas_porte', 'cartas_solicitud_estado_index')) {
                $table->dropIndex('cartas_solicitud_estado_index');
            }
            if (! $this->indexExiste('cartas_porte', 'cartas_porte_numero_index')) {
                $table->index('numero', 'cartas_porte_numero_index');
            }
        });

        Schema::table('facturas', function (Blueprint $table) {
            if ($this->indexExiste('facturas', 'facturas_numero_unique')) {
                $table->dropUnique('facturas_numero_unique');
            }
        });
    }

    private function indexExiste(string $tabla, string $indice): bool
    {
        return collect(Schema::getIndexes($tabla))->contains(fn ($i) => ($i['name'] ?? '') === $indice);
    }
};
