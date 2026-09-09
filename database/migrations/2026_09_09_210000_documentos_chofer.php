<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normaliza los documentos de los choferes (licencia, categorías, chequeo
 * médico, recalificación, psicométrico, limitaciones) en tabla propia
 * `documentos_chofer` + pivote `licencia_categorias`, moviendo los datos de
 * las columnas de `bolsa` y eliminándolas después.
 *
 * Tipos de documento: LICENCIA, CHEQUEO_MEDICO, RECALIFICACION, PSICOMETRICO.
 * El campo `limitaciones` viaja con el documento LICENCIA (notas).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documentos_chofer')) {
            Schema::create('documentos_chofer', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_bolsa');
                $table->string('tipo', 30); // LICENCIA | CHEQUEO_MEDICO | RECALIFICACION | PSICOMETRICO
                $table->string('numero', 50)->nullable();
                $table->date('emision')->nullable();
                $table->date('vencimiento')->nullable();
                $table->string('notas')->nullable(); // limitaciones de la licencia
                $table->boolean('vigente')->default(true);
                $table->unsignedBigInteger('id_entidad')->nullable();
                $table->timestamps();

                $table->foreign('id_bolsa')->references('id')->on('bolsa')->cascadeOnDelete();
                $table->index(['tipo', 'vencimiento']);
                $table->index('id_bolsa');
            });
        }

        if (! Schema::hasTable('licencia_categorias')) {
            Schema::create('licencia_categorias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_bolsa');
                $table->string('categoria', 3); // A | B | C | D | E
                $table->timestamps();

                $table->foreign('id_bolsa')->references('id')->on('bolsa')->cascadeOnDelete();
                $table->unique(['id_bolsa', 'categoria']);
                $table->index('categoria');
            });
        }

        // 1) Mover datos de bolsa → documentos_chofer.
        $tipos = [
            'LICENCIA' => ['licencia_emision', 'licencia_vencimiento', 'limitaciones'],
            'CHEQUEO_MEDICO' => ['chequeo_medico_emision', 'chequeo_medico_vencimiento', null],
            'RECALIFICACION' => ['reubicacion_emision', 'reubicacion_vencimiento', null],
            'PSICOMETRICO' => ['psicometrico_emision', 'psicometrico_vencimiento', null],
        ];

        foreach ($tipos as $tipo => [$colEmision, $colVencimiento, $colNotas]) {
            $filas = DB::table('bolsa')
                ->where(fn ($q) => $q->whereNotNull($colEmision)->orWhereNotNull($colVencimiento))
                ->get(array_merge(['id', 'id_entidad'], array_filter([$colEmision, $colVencimiento, $colNotas])));

            foreach ($filas as $fila) {
                $existe = DB::table('documentos_chofer')
                    ->where('id_bolsa', $fila->id)
                    ->where('tipo', $tipo)
                    ->exists();

                if (! $existe) {
                    DB::table('documentos_chofer')->insert([
                        'id_bolsa' => $fila->id,
                        'tipo' => $tipo,
                        'numero' => $tipo === 'LICENCIA' ? DB::table('bolsa')->where('id', $fila->id)->value('licencia') : null,
                        'emision' => $fila->{$colEmision},
                        'vencimiento' => $fila->{$colVencimiento},
                        'notas' => $colNotas ? $fila->{$colNotas} : null,
                        'vigente' => true,
                        'id_entidad' => $fila->id_entidad,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 2) Categorías de licencia: texto "B,C" → pivote.
        $catFilas = DB::table('bolsa')
            ->whereNotNull('categorias_licencia')
            ->where('categorias_licencia', '!=', '')
            ->get(['id', 'categorias_licencia']);

        foreach ($catFilas as $fila) {
            foreach (preg_split('/[,\s]+/', mb_strtoupper((string) $fila->categorias_licencia)) ?: [] as $cat) {
                $cat = trim($cat);
                if ($cat === '' || strlen($cat) > 3) {
                    continue;
                }
                DB::table('licencia_categorias')->updateOrInsert(
                    ['id_bolsa' => $fila->id, 'categoria' => $cat],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // 3) Quitar columnas de bolsa (dropear FKs primero si existen).
        $cols = [
            'tiene_licencia', 'categorias_licencia', 'licencia', 'licencia_emision', 'licencia_vencimiento',
            'limitaciones', 'chequeo_medico_emision', 'chequeo_medico_vencimiento',
            'reubicacion_emision', 'reubicacion_vencimiento',
            'psicometrico_emision', 'psicometrico_vencimiento',
        ];
        foreach ($cols as $col) {
            if (Schema::hasColumn('bolsa', $col)) {
                Schema::table('bolsa', function (Blueprint $table) use ($col) {
                    $table->dropColumn($col);
                });
            }
        }
    }

    public function down(): void
    {
        // Restaurar columnas de bolsa.
        Schema::table('bolsa', function (Blueprint $table) {
            $table->boolean('tiene_licencia')->default(false);
            $table->string('categorias_licencia', 100)->nullable();
            $table->string('licencia', 50)->nullable();
            $table->date('licencia_emision')->nullable();
            $table->date('licencia_vencimiento')->nullable();
            $table->string('limitaciones')->nullable();
            $table->date('chequeo_medico_emision')->nullable();
            $table->date('chequeo_medico_vencimiento')->nullable();
            $table->date('reubicacion_emision')->nullable();
            $table->date('reubicacion_vencimiento')->nullable();
            $table->date('psicometrico_emision')->nullable();
            $table->date('psicometrico_vencimiento')->nullable();
        });

        // Reponer datos desde documentos_chofer.
        $mapa = [
            'LICENCIA' => ['licencia_emision', 'licencia_vencimiento', 'limitaciones'],
            'CHEQUEO_MEDICO' => ['chequeo_medico_emision', 'chequeo_medico_vencimiento', null],
            'RECALIFICACION' => ['reubicacion_emision', 'reubicacion_vencimiento', null],
            'PSICOMETRICO' => ['psicometrico_emision', 'psicometrico_vencimiento', null],
        ];

        foreach (DB::table('documentos_chofer')->get() as $doc) {
            $set = [];
            [$colEmision, $colVencimiento, $colNotas] = $mapa[$doc->tipo] ?? [null, null, null];
            if ($colEmision) {
                $set = array_filter([
                    $colEmision => $doc->emision,
                    $colVencimiento => $doc->vencimiento,
                ], fn ($v) => $v !== null);
                if ($colNotas && $doc->notas) {
                    $set[$colNotas] = $doc->notas;
                }
                if ($set) {
                    DB::table('bolsa')->where('id', $doc->id_bolsa)->update($set);
                }
            }
        }

        // Categorías pivote → texto.
        foreach (DB::table('licencia_categorias')->get()->groupBy('id_bolsa') as $idBolsa => $cats) {
            DB::table('bolsa')->where('id', $idBolsa)->update([
                'categorias_licencia' => $cats->pluck('categoria')->implode(','),
                'tiene_licencia' => true,
            ]);
        }

        Schema::dropIfExists('licencia_categorias');
        Schema::dropIfExists('documentos_chofer');
    }
};
