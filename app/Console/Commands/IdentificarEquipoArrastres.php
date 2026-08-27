<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Identifica el tipo de equipo de los "tipos de vehículo" clase=arrastre a
 * partir del legacy (EMCARGA), donde TODO arrastre tiene equipo definido en
 * tec_tipoarrastres.idtipoequipos.
 *
 * Cadena de resolución:
 *   tipo_vehiculos.id_tipo_arrastre (= tipos_arrastres.id = legacy idtipoarrastres)
 *     -> tec_tipoarrastres.idtipoequipos
 *     -> tec_tipoequipos.tipoequipos (normalizado) -> tipos_equipos.id
 *
 * Idempotente. DRY-RUN por defecto; usar --aplicar para escribir.
 *
 * Uso:
 *   php artisan zafiro:identificar-equipo-arrastres
 *   php artisan zafiro:identificar-equipo-arrastres --aplicar
 */
class IdentificarEquipoArrastres extends Command
{
    protected $signature = 'zafiro:identificar-equipo-arrastres {--aplicar : Escribe los cambios en la BD}';

    protected $description = 'Puebla tipo_vehiculos.id_tipo_equipo para arrastres desde el legacy';

    public function handle(): int
    {
        $aplicar = (bool) $this->option('aplicar');

        $mapTE = $this->mapaTipoEquipo();

        $nuevo = DB::connection('mysql');
        $legacy = DB::connection('legacy');

        // idtipoarrastres -> idtipoequipos (legacy)
        $legTA = $legacy->table('tec_tipoarrastres')
            ->whereNotNull('idtipoequipos')
            ->pluck('idtipoequipos', 'idtipoarrastres');

        $filas = $nuevo->table('tipo_vehiculos')
            ->where('clase', 'arrastre')
            ->whereNotNull('id_tipo_arrastre')
            ->select('id', 'id_tipo_arrastre', 'id_tipo_equipo')
            ->get();

        $cambios = 0;
        $sinMapa = 0;
        $detalle = [];

        foreach ($filas as $f) {
            $legId = $legTA[$f->id_tipo_arrastre] ?? null;
            if ($legId === null) {
                $sinMapa++;
                continue;
            }
            $nuevoTeId = $mapTE[$legId] ?? null;
            if ($nuevoTeId === null) {
                $sinMapo2 = true;
                $sinMapa++;
                continue;
            }
            if ((int) $f->id_tipo_equipo === (int) $nuevoTeId) {
                continue;
            }
            $cambios++;
            $detalle[] = "tipo_vehiculos#{$f->id}: id_tipo_equipo {$f->id_tipo_equipo} -> {$nuevoTeId} (legacy eq {$legId})";
            if ($aplicar) {
                $nuevo->table('tipo_vehiculos')
                    ->where('id', $f->id)
                    ->update(['id_tipo_equipo' => $nuevoTeId, 'updated_at' => now()]);
            }
        }

        $modo = $aplicar ? 'APLICADO' : 'DRY-RUN (no se escribió)';
        $this->info("Modo: {$modo}");
        $this->info("Filas evaluadas: " . $filas->count());
        $this->info("Cambios " . ($aplicar ? 'aplicados' : 'pendientes') . ": {$cambios}");
        $this->info("Sin mapeo de equipo legacy: {$sinMapa}");

        if (! $aplicar) {
            $this->info('--- Detalle (primeros 40) ---');
            foreach (array_slice($detalle, 0, 40) as $d) {
                $this->line("  {$d}");
            }
            if (count($detalle) > 40) {
                $this->info('  ... (' . count($detalle) . ' en total)');
            }
        }

        return self::SUCCESS;
    }

    /**
     * Mapa legacy idtipoequipos -> nuevo tipos_equipos.id, usando el nombre
     * normalizado (aplica absorciones del TiposEquiposNormalizer y quita acentos).
     */
    private function mapaTipoEquipo(): array
    {
        // Absorciones: nombre final -> [nombres absorbidos].
        $grupos = [
            'CUÑA TRACTORA' => ['CUÑAS TRACTORAS'],
            'OMNIBUS' => [],
            'CISTERNA' => ['CAMION CISTERNA AGUA'],
            'AUTO' => ['AUTO LIGERO', 'AUTO ESPECIAL', 'AUTO ESPECIALIZADO', 'AUTO PASEO'],
            'GRUA' => ['CAMION GRUA'],
            'VOLTEO' => ['CAMION VOLTEO', 'S/R VOLTEO'],
        ];
        $absorbidoAFinal = [];
        foreach ($grupos as $final => $abs) {
            foreach ($abs as $a) {
                $absorbidoAFinal[mb_strtoupper($a)] = $final;
            }
        }

        $norm = function (string $nombre): string {
            $s = mb_strtoupper(trim($nombre));
            $s = strtr($s, [
                'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
                'Ñ' => 'N', 'Â' => 'A', 'Ê' => 'E', 'Î' => 'I', 'Ô' => 'O', 'Û' => 'U',
            ]);
            return $s;
        };

        // Nombres nuevos normalizados -> id.
        $nuevos = DB::connection('mysql')->table('tipos_equipos')->pluck('id', 'nombre');
        $nuevosNorm = [];
        foreach ($nuevos as $nombre => $id) {
            $nuevosNorm[$norm($nombre)] = $id;
        }

        // Legacy idtipoequipos -> nombre -> final normalizado -> nuevo id.
        $legacyTE = DB::connection('legacy')->table('tec_tipoequipos')
            ->pluck('tipoequipos', 'idtipoequipos');

        $map = [];
        foreach ($legacyTE as $legId => $nombre) {
            $u = mb_strtoupper(trim($nombre));
            $final = $absorbidoAFinal[$u] ?? (array_key_exists($u, $grupos) ? $u : $u);
            $map[$legId] = $nuevosNorm[$norm($final)] ?? null;
        }

        return $map;
    }
}
