<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconcilia tractivos/arrastres del nuevo esquema (Zafiro) para que sus
 * VALORES coincidan exactamente con el legacy (EMCARGA).
 *
 * Por defecto (sin --forzar): hace backfill de campos vacíos y corrige el
 * linkage roto id_tipo_vehiculo.
 *
 * Con --forzar: SOBRESCRIBE todos los campos comparables con el valor legacy
 * resuelto (texto o FK), de modo que la BD nueva refleje fielmente el legacy.
 *
 * Los FK se resuelven por nombre contra las tablas de referencia nuevas:
 *   tipo_equipo (normalizado), tipo_combustible, colores (catalogo_items),
 *   estado_componente, entidad.
 *
 * DRY-RUN por defecto. Usar --aplicar para escribir. Idempotente.
 *
 * Uso:
 *   php artisan zafiro:reconciliar-vehiculos
 *   php artisan zafiro:reconciliar-vehiculos --forzar
 *   php artisan zafiro:reconciliar-vehiculos --forzar --aplicar
 */
class ReconciliarVehiculos extends Command
{
    protected $signature = 'zafiro:reconciliar-vehiculos {--forzar : Sobrescribe TODOS los campos con el valor legacy} {--aplicar : Escribe los cambios en la BD}';

    protected $description = 'Reconcilia tractivos/arrastres nuevos para que coincidan exactamente con el legacy';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');
        $nuevo = DB::connection('mysql');
        $aplicar = (bool) $this->option('aplicar');
        $forzar = (bool) $this->option('forzar');

        // ---- Mapas de resolución legacy -> nuevo ----

        // tipo_vehiculos canonico: id_tipo_tractivo (legacy) => tipo_vehiculos.id
        $mapTractivo = $nuevo->table('tipo_vehiculos')
            ->where('clase', 'tractivo')->whereNotNull('id_tipo_tractivo')
            ->pluck('id', 'id_tipo_tractivo');
        $mapArrastre = $nuevo->table('tipo_vehiculos')
            ->where('clase', 'arrastre')->whereNotNull('id_tipo_arrastre')
            ->pluck('id', 'id_tipo_arrastre');

        $marcas = $legacy->table('tec_marca')->pluck('marca', 'idmarca');
        $modelos = $legacy->table('tec_modelo')->pluck('modelo', 'idmodelo');

        // tipo_equipo (normalizado vía TiposEquiposNormalizer)
        $legacyTE = $legacy->table('tec_tipoequipos')->pluck('tipoequipos', 'idtipoequipos');
        $newTE = $nuevo->table('tipos_equipos')->pluck('id', 'nombre');
        $GRUPOS = [
            'CUÑA TRACTORA' => ['CUÑAS TRACTORAS'],
            'OMNIBUS' => [],
            'CISTERNA' => ['CAMION CISTERNA AGUA'],
            'AUTO' => ['AUTO LIGERO', 'AUTO ESPECIAL'],
            'GRUA' => ['CAMION GRUA'],
            'VOLTEO' => ['CAMION VOLTEO', 'S/R VOLTEO'],
        ];
        $absorbidoAFinal = [];
        foreach ($GRUPOS as $final => $abs) {
            foreach ($abs as $a) {
                $absorbidoAFinal[mb_strtoupper($a)] = $final;
            }
        }
        $mapTipoEquipo = [];
        foreach ($legacyTE as $id => $nombre) {
            $u = mb_strtoupper(trim($nombre));
            $canon = $absorbidoAFinal[$u] ?? (array_key_exists($u, $GRUPOS) ? $u : $u);
            $mapTipoEquipo[$id] = $newTE[$canon] ?? null;
        }

        // tipo_combustible por nombre
        $legacyTC = $legacy->table('tec_tipocombustibles')->pluck('tipocombustibles', 'idtipocombustibles');
        $newTC = $nuevo->table('tipos_combustibles')->pluck('id', 'nombre');
        $mapTipoComb = [];
        foreach ($legacyTC as $id => $nombre) {
            $mapTipoComb[$id] = $newTC[mb_strtoupper(trim($nombre))] ?? null;
        }

        // colores via catalogo_items (origen_id o nombre)
        $catColores = $nuevo->table('catalogo_items')->where('tipo', 'colores')->whereNull('deleted_at')->get();
        $catPorOrigen = $catColores->pluck('id', 'origen_id');
        $catPorNombre = $catColores->mapWithKeys(fn ($c) => [mb_strtolower($c->nombre) => $c->id]);
        $legacyCol = $legacy->table('tec_colores')->pluck('colores', 'idcolores');
        $mapColor = [];
        foreach ($legacyCol as $id => $nombre) {
            $mapColor[$id] = $catPorOrigen[$id] ?? ($catPorNombre[mb_strtolower(trim($nombre))] ?? null);
        }

        // estados_componentes por nombre
        $legacyEst = $legacy->table('tec_tipoestados')->pluck('tipoestados', 'idtipoestados');
        $newEst = $nuevo->table('estados_componentes')->pluck('id', 'nombre');
        $newEstNombre = $nuevo->table('estados_componentes')->pluck('nombre', 'id');
        $mapEstado = [];
        foreach ($legacyEst as $id => $nombre) {
            $mapEstado[$id] = $newEst[mb_strtoupper(trim($nombre))] ?? null;
        }

        // entidades: el nuevo preserva el id legacy (identidades), así que mapeo directo id->id,
        // con respaldo por nombre si algún id no coincide.
        $legacyEnt = $legacy->table('rh_entidades')->pluck('nombentidad', 'identidades');
        $newEntPorId = $nuevo->table('entidades')->pluck('id', 'id');
        $newEntPorNombre = $nuevo->table('entidades')->pluck('id', 'nombre')->mapWithKeys(fn ($v, $k) => [mb_strtolower($k) => $v]);
        $mapEntidad = [];
        foreach ($legacyEnt as $id => $nombre) {
            $mapEntidad[$id] = $newEntPorId[$id] ?? ($newEntPorNombre[mb_strtolower(trim($nombre))] ?? null);
        }

        $cambios = ['tractores' => 0, 'arrastres' => 0];
        $fallos = [];
        $detalle = [];

        // ---------------- TRACTIVOS ----------------
        $legacyT = $legacy->table('tec_tractivos as t')
            ->leftJoin('tec_tipotractivos as tp', 'tp.idtipotractivos', '=', 't.idtipotractivos')
            ->where('t.idgrupo', '!=', 8)
            ->select('t.*', 'tp.fabricacion as tp_fabricacion', 'tp.idtipoequipos', 'tp.idtipocombustibles', 'tp.idmarca', 'tp.idmodelo')
            ->get();

        foreach ($legacyT as $l) {
            $n = $nuevo->table('tractivos')->where('id', $l->idtractivos)->first();
            if (! $n) {
                continue;
            }
            $upd = [];

            // Linkage id_tipo_vehiculo (siempre corregir si difiere)
            $tv = $mapTractivo[$l->idtipotractivos] ?? null;
            if ($tv !== null && (int) $n->id_tipo_vehiculo !== (int) $tv) {
                $upd['id_tipo_vehiculo'] = $tv;
            }

            $candidatos = [
                'codigo' => $this->txt($l->codtractivo),
                'placa' => $this->txt($l->chapa),
                'marca' => $this->txt($marcas[$l->idmarca] ?? null),
                'modelo' => $this->txt($modelos[$l->idmodelo] ?? null),
                'anno' => $this->anno($l->tp_fabricacion),
                'color' => $this->txt($legacyCol[$l->idcolorprimario] ?? null),
                'vin' => $this->txt($l->vin),
                'numero_chasis' => $this->txt($l->chassis),
                'tara' => $this->num($l->tara),
                'cap_deposito' => $this->num($l->captanque),
                'cap_hidraulico' => $this->num($l->caphidraulico),
                'cta_combustible' => $this->txt($l->ctacomb),
                'indice_consumo' => $this->num($l->indice),
                'indice_aceite' => $this->num($l->indiceac),
                'capacidad_toneladas' => $this->num($l->capacidad),
                'kilometraje_actual' => $this->num($l->kmsacum),
                'kms_disp' => $this->num($l->kmsdisp),
                'kms_plan_mtto' => $this->num($l->kmsplanmtto),
                'gps' => $this->txt($l->gps),
                'fecha_alta' => $this->fecha($l->falta),
                'fecha_baja' => $this->fecha($l->fbaja),
                'id_tipo_equipo' => $mapTipoEquipo[$l->idtipoequipos] ?? null,
                'id_tipo_combustible' => $mapTipoComb[$l->idtipocombustibles] ?? null,
                'id_color_primario' => $mapColor[$l->idcolorprimario] ?? null,
                'id_color_secundario' => $mapColor[$l->idcolorsecundario] ?? null,
                'id_tipo_estado' => $mapEstado[$l->idtipoestados] ?? null,
                'id_entidad' => $mapEntidad[$l->idunidad] ?? null,
                'estado' => $this->txt($legacyEst[$l->idtipoestados] ?? null),
            ];

            foreach ($candidatos as $col => $val) {
                if (! $this->debeAplicar($forzar, $n->$col ?? null, $val, $col)) {
                    continue;
                }
                $upd[$col] = $val;
            }

            // Evitar violar UNIQUE en placa/codigo (el legacy tiene duplicados):
            // si el valor ya existe en OTRO registro, no forzamos esa columna.
            $this->evitarUnico($nuevo, 'tractivos', $l->idtractivos, $upd, 'placa');
            $this->evitarUnico($nuevo, 'tractivos', $l->idtractivos, $upd, 'codigo');

            if (! empty($upd)) {
                $cambios['tractores']++;
                $detalle[] = "tractivos#{$l->idtractivos}: " . implode(',', array_keys($upd));
                if ($aplicar) {
                    try {
                        $nuevo->table('tractivos')->where('id', $l->idtractivos)->update($upd);
                    } catch (\Throwable $e) {
                        $fallos[] = "tractivos#{$l->idtractivos}: " . $e->getMessage();
                    }
                }
            }
        }

        // ---------------- ARRASTRES ----------------
        $legacyA = $legacy->table('tec_tractivos as t')
            ->where('t.idgrupo', '=', 8)
            ->select('t.*')
            ->get();

        foreach ($legacyA as $l) {
            $n = $nuevo->table('arrastres')->where('id', $l->idtractivos)->first();
            if (! $n) {
                continue;
            }
            $upd = [];

            $tv = $mapArrastre[$l->idtipotractivos] ?? null;
            if ($tv !== null && (int) $n->id_tipo_vehiculo !== (int) $tv) {
                $upd['id_tipo_vehiculo'] = $tv;
            }

            $candidatos = [
                'codigo' => $this->txt($l->codtractivo),
                'placa' => $this->txt($l->chapa),
                'id_color_primario' => $mapColor[$l->idcolorprimario] ?? null,
                'id_color_secundario' => $mapColor[$l->idcolorsecundario] ?? null,
                'id_entidad' => $mapEntidad[$l->idunidad] ?? null,
                'tara' => $this->num($l->tara),
                'indice_aceite' => $this->num($l->indiceac),
                'estado' => $this->txt($legacyEst[$l->idtipoestados] ?? null),
                'fecha_alta' => $this->fecha($l->falta),
                'fecha_baja' => $this->fecha($l->fbaja),
            ];

            foreach ($candidatos as $col => $val) {
                if (! $this->debeAplicar($forzar, $n->$col ?? null, $val, $col)) {
                    continue;
                }
                $upd[$col] = $val;
            }

            $this->evitarUnico($nuevo, 'arrastres', $l->idtractivos, $upd, 'placa');
            $this->evitarUnico($nuevo, 'arrastres', $l->idtractivos, $upd, 'codigo');

            if (! empty($upd)) {
                $cambios['arrastres']++;
                $detalle[] = "arrastres#{$l->idtractivos}: " . implode(',', array_keys($upd));
                if ($aplicar) {
                    try {
                        $nuevo->table('arrastres')->where('id', $l->idtractivos)->update($upd);
                    } catch (\Throwable $e) {
                        $fallos[] = "arrastres#{$l->idtractivos}: " . $e->getMessage();
                    }
                }
            }
        }

        $modo = $aplicar ? 'APLICADO' : 'DRY-RUN (no se escribió)';
        $this->info("Modo: {$modo} | Forzar: " . ($forzar ? 'SI' : 'NO'));
        $this->info("Filas que cambiarían/ cambiaron: " . json_encode($cambios));
        if (! empty($fallos)) {
            $this->error('Fallos (' . count($fallos) . '):');
            foreach (array_slice($fallos, 0, 20) as $f) {
                $this->line("  {$f}");
            }
        }
        if (! $aplicar) {
            $this->info('--- Detalle (primeros 30) ---');
            foreach (array_slice($detalle, 0, 30) as $d) {
                $this->line("  {$d}");
            }
            if (count($detalle) > 30) {
                $this->info('  ... (' . count($detalle) . ' en total)');
            }
        }

        return self::SUCCESS;
    }

    private function debeAplicar(bool $forzar, $current, $target, string $col): bool
    {
        // placa no puede quedar nula (NOT NULL + UNIQUE)
        if ($col === 'placa' && ($target === null || $target === '')) {
            return false;
        }
        if ($forzar) {
            if ($target === null) {
                return false;
            }
            // comparación numérica para evitar falsos positivos ("400.00" vs 400)
            if (is_numeric($current) && is_numeric($target)) {
                return (float) $current != (float) $target;
            }
            return (string) $current !== (string) $target;
        }
        // backfill: solo si el nuevo está vacío y el legacy tiene valor
        $vacío = $current === null || $current === '' || (is_numeric($current) && (float) $current == 0);
        return $vacío && $target !== null && $target !== '';
    }

    /**
     * Si el valor de una columna UNIQUE (placa/codigo) ya existe en otro registro
     * de la misma tabla, lo quita del update para no violar la restricción
     * (el legacy tiene duplicados que la nueva BD no permite).
     */
    private function evitarUnico($nuevo, string $tabla, $id, array &$upd, string $col): void
    {
        if (! array_key_exists($col, $upd) || $upd[$col] === null || $upd[$col] === '') {
            return;
        }
        $existe = $nuevo->table($tabla)
            ->where($col, $upd[$col])
            ->where('id', '!=', $id)
            ->exists();
        if ($existe) {
            unset($upd[$col]);
        }
    }

    private function txt($v): ?string
    {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : $v;
    }

    private function num($v): ?float
    {
        if (! is_numeric($v)) {
            return null;
        }
        $f = (float) $v;
        if ($f <= 0 || $f >= 2000000000) {
            return null; // centinela/inválido
        }
        return $f;
    }

    private function anno($v): ?int
    {
        if (! is_numeric($v)) {
            return null;
        }
        $y = (int) $v;
        return ($y > 1900 && $y < 2100) ? $y : null;
    }

    private function fecha($v): ?string
    {
        if (empty($v) || $v === '0000-00-00' || $v === '0000-00-00 00:00:00') {
            return null;
        }
        $ts = strtotime($v);
        if ($ts === false) {
            return null;
        }
        $y = (int) date('Y', $ts);
        if ($y < 1900) {
            return null;
        }
        return date('Y-m-d', $ts);
    }
}
