<?php

use App\Support\CatalogoSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El catálogo de marcas arrastraba un campo "tipo" vestigial (de la
 * unificación de codificadores) que no debe mostrarse en el grid ni en
 * el formulario. Se oculta quitándolo del JSON `fields` de catalogo_tipos
 * (la fuente de verdad del esquema). La columna `tipo` en la tabla `marcas`
 * queda intacta (inofensiva, sin uso en la app).
 */
return new class extends Migration
{
    public function up(): void
    {
        $raw = DB::table('catalogo_tipos')->where('tipo', 'marcas')->value('fields');

        $campos = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?? []);
        if (is_array($campos) && array_key_exists('tipo', $campos)) {
            unset($campos['tipo']);
            DB::table('catalogo_tipos')->where('tipo', 'marcas')->update(['fields' => json_encode($campos)]);
        }

        CatalogoSchema::flushCache();
    }

    public function down(): void
    {
        $raw = DB::table('catalogo_tipos')->where('tipo', 'marcas')->value('fields');

        $campos = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?? []);
        $campos['tipo'] = ['label' => 'Tipo', 'type' => 'text'];
        DB::table('catalogo_tipos')->where('tipo', 'marcas')->update(['fields' => json_encode($campos)]);

        CatalogoSchema::flushCache();
    }
};
