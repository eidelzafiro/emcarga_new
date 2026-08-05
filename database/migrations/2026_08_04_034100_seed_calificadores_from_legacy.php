<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->legacyDisponible()) {
            return;
        }

        $legacy = DB::connection('legacy')
            ->table('rh_tipocalificadores')
            ->get();

        foreach ($legacy as $row) {
            DB::table('calificadores')->updateOrInsert(
                ['codigo' => (string) $row->idtipocalificadores],
                [
                    'nombre' => $row->nombcalificador,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('calificadores')->truncate();
    }

    private function legacyDisponible(): bool
    {
        try {
            return DB::connection('legacy')->getPdo() !== null;
        } catch (Throwable) {
            return false;
        }
    }
};
