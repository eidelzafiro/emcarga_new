<?php

use App\Models\CatalogoTipo;
use App\Support\CatalogoSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('catalogo_tipos', 'fields')) {
            Schema::table('catalogo_tipos', function (Blueprint $table) {
                $table->json('fields')->nullable()->after('tabla_legacy');
            });
        }

        // Backfill: persiste explícitamente los campos por defecto de cada tipo.
        // A partir de aquí `catalogo_tipos.fields` es la fuente de verdad en
        // runtime; CatalogoSchema lee esta columna (con degradación a los
        // valores por defecto si el tipo no la tiene).
        foreach (CatalogoTipo::whereNotNull('tipo')->pluck('tipo') as $tipo) {
            $campos = CatalogoSchema::defaultFields($tipo);
            if ($campos !== []) {
                DB::table('catalogo_tipos')
                    ->where('tipo', $tipo)
                    ->update(['fields' => json_encode($campos, JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('catalogo_tipos', 'fields')) {
            Schema::table('catalogo_tipos', function (Blueprint $table) {
                $table->dropColumn('fields');
            });
        }
    }
};
