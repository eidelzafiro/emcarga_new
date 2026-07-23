<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EtlMapeoTest extends TestCase
{
    use RefreshDatabase;

    private array $legacy;

    private array $nuevo;

    private array $mapeo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->legacy = json_decode(
            file_get_contents(base_path('database/etl/legacy_schema.json')),
            true
        );

        $this->nuevo = [];
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations'") as $t) {
            $cols = [];
            foreach (DB::select("PRAGMA table_info('{$t->name}')") as $c) {
                $cols[$c->name] = [
                    'tipo' => strtolower($c->type),
                    'anulable' => ! (bool) $c->notnull,
                    'default' => $c->dflt_value,
                ];
            }
            $this->nuevo[$t->name] = $cols;
        }

        $this->mapeo = require base_path('config/etl.php');
    }

    public function test_mapeo_tiene_tablas(): void
    {
        $this->assertGreaterThan(0, count($this->mapeo['tablas']));
    }

    public function test_tablas_legacy_existen(): void
    {
        $errores = [];
        foreach ($this->mapeo['tablas'] as $nueva => $cfg) {
            if (! isset($this->legacy[$cfg['legacy']])) {
                $errores[] = "{$nueva}: tabla legacy {$cfg['legacy']} no existe en el dump";
            }
        }
        $this->assertEmpty($errores, implode("\n", $errores));
    }

    public function test_tablas_nuevas_existen(): void
    {
        $errores = [];
        foreach ($this->mapeo['tablas'] as $nueva => $cfg) {
            if (! isset($this->nuevo[$nueva])) {
                $errores[] = "{$nueva}: tabla nueva no existe en el esquema";
            }
        }
        $this->assertEmpty($errores, implode("\n", $errores));
    }

    public function test_columnas_legacy_existen(): void
    {
        $errores = [];
        foreach ($this->mapeo['tablas'] as $nueva => $cfg) {
            $legacyT = $cfg['legacy'];
            if (! isset($this->legacy[$legacyT])) {
                continue;
            }
            foreach ($cfg['columnas'] ?? [] as $colL => $colN) {
                if (! isset($this->legacy[$legacyT]['columnas'][$colL])) {
                    $errores[] = "{$nueva}: columna legacy {$legacyT}.{$colL} no existe";
                }
            }
        }
        $this->assertEmpty($errores, implode("\n", $errores));
    }

    public function test_columnas_nuevas_existen(): void
    {
        $errores = [];
        foreach ($this->mapeo['tablas'] as $nueva => $cfg) {
            if (! isset($this->nuevo[$nueva])) {
                continue;
            }
            foreach ($cfg['columnas'] ?? [] as $colL => $colN) {
                if (! isset($this->nuevo[$nueva][$colN])) {
                    $errores[] = "{$nueva}: columna nueva {$nueva}.{$colN} no existe";
                }
            }
        }
        $this->assertEmpty($errores, implode("\n", $errores));
    }

    public function test_not_null_sin_origen_tienen_default(): void
    {
        $errores = [];
        $omitir = ['id', 'created_at', 'updated_at', 'deleted_at'];
        foreach ($this->mapeo['tablas'] as $nueva => $cfg) {
            if (! isset($this->nuevo[$nueva])) {
                continue;
            }
            $colsMap = array_flip($cfg['columnas'] ?? []);
            $defaults = $cfg['defaults'] ?? [];
            foreach ($this->nuevo[$nueva] as $colN => $info) {
                if (in_array($colN, $omitir, true)) {
                    continue;
                }
                if (isset($colsMap[$colN]) || isset($defaults[$colN])) {
                    continue;
                }
                if ($info['anulable'] || $info['default'] !== null) {
                    continue;
                }
                $errores[] = "{$nueva}.{$colN} NOT NULL sin mapeo ni default";
            }
        }
        $this->assertEmpty($errores, implode("\n", $errores));
    }

    public function test_claves_existen_en_ambos_esquemas(): void
    {
        $errores = [];
        foreach ($this->mapeo['tablas'] as $nueva => $cfg) {
            foreach ($cfg['clave'] ?? [] as $col) {
                $colsMap = array_flip($cfg['columnas'] ?? []);
                if (! isset($colsMap[$col])) {
                    $errores[] = "{$nueva}: columna clave {$col} no está mapeada en columnas";
                }
                if (! isset($this->nuevo[$nueva][$col])) {
                    $errores[] = "{$nueva}: columna clave {$col} no existe en tabla nueva";
                }
            }
        }
        $this->assertEmpty($errores, implode("\n", $errores));
    }

    public function test_mapeo_perfiles_es_valido(): void
    {
        $perfiles = $this->mapeo['mapeo_perfiles'];
        $roles = ['RECHUM', 'TECNICA', 'COMERCIAL', 'CONTABILIDAD', 'ADMIN', 'OPERATIVOS'];
        foreach ($perfiles as $id => $rol) {
            $this->assertContains($rol, $roles, "Perfil {$id} → {$rol} no es un rol válido");
        }
    }
}
