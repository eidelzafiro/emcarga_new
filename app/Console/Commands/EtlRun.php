<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use App\Services\Etl\EtlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando ETL legacy → nuevo esquema (Fase 3).
 *
 * Uso:
 *   php artisan emcarga:etl                 ETL completo (usuarios + tablas mapeadas)
 *   php artisan emcarga:etl --solo=users    Solo usuarios (con password_histories)
 *   php artisan emcarga:etl --solo=clientes Solo una tabla del mapeo
 *   php artisan emcarga:etl --validar       Solo conteos old vs new, sin migrar
 */
class EtlRun extends Command
{
    protected $signature = 'emcarga:etl
                            {--solo= : Migrar solo una tabla (users o una del mapeo)}
                            {--validar : Solo muestra conteos legacy vs nueva}
                            {--chunk=1000 : Tamaño del lote de lectura}
                            {--no-fresh : Omite migrate:fresh --seed (para re-ejecutar sin reiniciar)}
                            {--no-salva : Omite la salva total automática tras el ETL}';

    protected $description = 'Migra datos del sistema legacy (CodeIgniter) al nuevo esquema (Fase 3)';

    public function handle(EtlService $etl): int
    {
        if ($this->option('validar')) {
            return $this->mostrarValidacion($etl);
        }

        if (! $this->option('no-fresh')) {
            $this->info('Preparando BD (migrate:fresh --seed)...');
            $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        }

        $solo = $this->option('solo');
        $chunk = (int) $this->option('chunk');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if (! $solo || $solo === 'users') {
            $this->info('Migrando usuarios (cod_usuarios → users + password_histories)...');
            $etl->migrarUsuarios(min($chunk, 500));
            $this->mostrarResultado($etl->getReporte());
        }

        $excluirDatos = config('etl.excluir_datos', []);
        foreach (array_keys(config('etl.tablas')) as $tabla) {
            if ($solo && $solo !== $tabla) {
                continue;
            }
            if (in_array($tabla, $excluirDatos) && ! $solo) {
                continue;
            }
            if ($tabla === 'consecutivos') {
                continue;  // migración dedicada abajo
            }
            if ($tabla === 'clientes') {
                continue;  // migración dedicada abajo
            }
            if ($tabla === 'distancias') {
                continue;  // migración dedicada abajo
            }
            if ($tabla === 'lugares') {
                continue;  // migración dedicada abajo (provincia/municipio desde catálogos)
            }
            if ($tabla === 'tractivos') {
                continue;  // migración dedicada abajo
            }
            if ($tabla === 'motores') {
                continue;  // migración dedicada abajo
            }
            if ($tabla === 'diferenciales') {
                continue;  // migración dedicada abajo
            }
            if ($tabla === 'neumaticos') {
                continue;  // migración dedicada abajo
            }
            if ($tabla === 'cajas') {
                continue;  // migración dedicada abajo
            }
            if ($tabla === 'lineas_mantenimiento') {
                continue;  // migración dedicada abajo
            }
            if ($tabla === 'historial_tractivos') {
                continue;  // migración dedicada abajo (solo año configurado)
            }
            if ($tabla === 'ordenes_taller') {
                continue;  // migración dedicada abajo (solo año configurado)
            }
            if ($tabla === 'arrastres') {
                continue;  // migración dedicada abajo (arrastres desde tractivos tipo-arrastre)
            }
            if (in_array($tabla, ['gastos_orden', 'motores_movimientos', 'neumaticos_movimientos', 'control_lubricantes'])) {
                continue;  // sin registros del año de negocio / catálogo sin encaje
            }
            if ($tabla === 'tarifas') {
                continue;  // migración dedicada abajo (com_tarifas46 → version 46 + com_tarifas → normal)
            }

            $this->info("Migrando {$tabla}...");
            $etl->migrarTabla($tabla, $chunk);
            $this->mostrarResultado($etl->getReporte(), $tabla);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Consecutivos: mapeo especial codigo/descripcion desde nombconsecutivo
        if (! $solo || $solo === 'consecutivos') {
            $this->info('Migrando consecutivos...');
            $etl->migrarConsecutivos($chunk);
            $this->mostrarResultado($etl->getReporte(), 'consecutivos');
        }

        // Clientes: campos legacy completos + sufijo -entidad en códigos duplicados
        if (! $solo || $solo === 'clientes') {
            $this->info('Migrando clientes...');
            $etl->migrarClientes($chunk);
            $this->mostrarResultado($etl->getReporte(), 'clientes');
        }

        // Distancias: duplicados origen-destino se resuelven por MENOR kms
        if (! $solo || $solo === 'distancias') {
            $this->info('Migrando distancias...');
            $etl->migrarDistancias();
            $this->mostrarResultado($etl->getReporte(), 'distancias');
        }

        // Lugares: provincia/municipio resueltos desde rh_provincias/rh_municipios
        if (! $solo || $solo === 'lugares') {
            $this->info('Migrando lugares...');
            $etl->migrarLugares($chunk);
            $this->mostrarResultado($etl->getReporte(), 'lugares');
        }

        // Tractivos: excluye dados de baja, sufijos -entidad, estado mapeado
        if (! $solo || $solo === 'tractivos') {
            $this->info('Migrando tractivos...');
            $etl->migrarTractivos($chunk);
            $this->mostrarResultado($etl->getReporte(), 'tractivos');
        }

        // Motores: id_tractivo NULL, marca/modelo desde catálogos, estado mapeado
        if (! $solo || $solo === 'motores') {
            $this->info('Migrando motores...');
            $etl->migrarMotores($chunk);
            $this->mostrarResultado($etl->getReporte(), 'motores');
        }

        // Diferenciales: id_tractivo NULL, ficha técnica, estado mapeado
        if (! $solo || $solo === 'diferenciales') {
            $this->info('Migrando diferenciales...');
            $etl->migrarDiferenciales($chunk);
            $this->mostrarResultado($etl->getReporte(), 'diferenciales');
        }

        // Neumáticos: marca/medida desde catálogos, ficha técnica, estado mapeado
        if (! $solo || $solo === 'neumaticos') {
            $this->info('Migrando neumáticos...');
            $etl->migrarNeumaticos($chunk);
            $this->mostrarResultado($etl->getReporte(), 'neumaticos');
        }

        // Cajas: id_tractivo NULL, marca/modelo desde catálogos, estado mapeado
        if (! $solo || $solo === 'cajas') {
            $this->info('Migrando cajas...');
            $etl->migrarCajas($chunk);
            $this->mostrarResultado($etl->getReporte(), 'cajas');
        }

        // Líneas de mantenimiento: sin PK legacy, idempotente por (tipo, km)
        if (! $solo || $solo === 'lineas_mantenimiento') {
            $this->info('Migrando líneas de mantenimiento...');
            $etl->migrarLineasMantenimiento($chunk);
            $this->mostrarResultado($etl->getReporte(), 'lineas_mantenimiento');
        }

        // Historial de tractivos: solo el año de negocio (2026), FKs validadas
        if (! $solo || $solo === 'historial_tractivos') {
            $this->info('Migrando historial de tractivos (año 2026)...');
            $etl->migrarHistorialTractivos(2026, $chunk);
            $this->mostrarResultado($etl->getReporte(), 'historial_tractivos');
        }

        // Órdenes de taller: solo el año de negocio (2026), idtipomtto 0 → SIN TIPO
        if (! $solo || $solo === 'ordenes_taller') {
            $this->info('Migrando órdenes de taller (año 2026)...');
            $etl->migrarOrdenesTaller(2026, $chunk);
            $this->mostrarResultado($etl->getReporte(), 'ordenes_taller');
        }

        // Arrastres: regla idgrupo=8 (grupo ARRASTRES), ficha desde tec_tipoarrastres
        if (! $solo || $solo === 'arrastres') {
            $this->info('Re-asociando arrastres (idgrupo=8, ficha tec_tipoarrastres)...');
            $etl->migrarArrastres($chunk);
            $this->mostrarResultado($etl->getReporte(), 'arrastres');
        }

        // Asociaciones tractivos ↔ arrastres (requiere arrastres ya migrados)
        if (! $solo || $solo === 'asociaciones') {
            $this->info('Migrando asociaciones tractivos ↔ arrastres...');
            $etl->migrarAsociaciones($chunk);
            $this->mostrarResultado($etl->getReporte(), 'arrastre_tractivo');
        }

        // Baterías: folio desde codigo, marca texto, id_tractivo NULL si 0
        if (! $solo || $solo === 'baterias') {
            $this->info('Migrando baterías...');
            $etl->migrarBaterias($chunk);
            $this->mostrarResultado($etl->getReporte(), 'baterias');
        }

        // Movimientos de baterías (requiere baterías ya migradas)
        if (! $solo || $solo === 'baterias_movimientos') {
            $this->info('Migrando movimientos de baterías...');
            $etl->migrarBateriasMovimientos($chunk);
            $this->mostrarResultado($etl->getReporte(), 'baterias_movimientos');
        }

        // Bolsa de empleados (requiere cargos + entidades ya migrados)
        if (! $solo || $solo === 'bolsa') {
            $this->info('Migrando bolsa de empleados...');
            $etl->migrarBolsa($chunk);
            $this->mostrarResultado($etl->getReporte(), 'bolsa');
        }

        // Hojas de ruta: solo el año de negocio (2026); entidad derivada del tractivo
        if (! $solo || $solo === 'hojas_ruta') {
            $this->info('Migrando hojas de ruta (año 2026)...');
            $etl->migrarHojasRuta(2026, $chunk);
            $this->mostrarResultado($etl->getReporte(), 'hojas_ruta');
        }

        // Cartas de porte (girado): com_girado solo 2026, numero = nrocp
        if (! $solo || $solo === 'cartas_porte') {
            $this->info('Migrando cartas de porte (girado, año 2026)...');
            $etl->migrarCartasPorte(2026, $chunk);
            $this->mostrarResultado($etl->getReporte(), 'cartas_porte');
        }

        // Facturas: com_rfactura solo 2026, numero re-numerado + numero_legacy
        if (! $solo || $solo === 'facturas') {
            $this->info('Migrando facturas (año 2026, numero re-numerado)...');
            $etl->migrarFacturas(2026, $chunk);
            $this->mostrarResultado($etl->getReporte(), 'facturas');
        }

        // Aforos: com_aforo solo 2026, vinculados a facturas ya migradas
        if (! $solo || $solo === 'aforos') {
            $this->info('Migrando aforos (año 2026)...');
            $etl->migrarAforos(2026, $chunk);
            $this->mostrarResultado($etl->getReporte(), 'aforos');
        }

        // Tarifas: com_tarifas46 (versión 46) + com_tarifas (versión normal, 117/118)
        if (! $solo || $solo === 'tarifas') {
            $this->info('Migrando tarifas (com_tarifas46 → versión 46, com_tarifas → normal)...');
            $etl->migrarTarifas($chunk);
            $this->mostrarResultado($etl->getReporte(), 'tarifas');
        }

        // Configuraciones de tarifa: una fila unificada en configuraciones_tarifa
        if (! $solo || $solo === 'configuraciones_tarifa') {
            $this->info('Migrando configuraciones de tarifa (unificada)...');
            $etl->migrarConfiguracionesTarifa();
            $this->mostrarResultado($etl->getReporte(), 'configuraciones_tarifa');
        }

        // Acuerdos de tarifas: com_taracuerdos → tarifas_acuerdos (FKs validadas)
        if (! $solo || $solo === 'tarifas_acuerdos') {
            $this->info('Migrando acuerdos de tarifas (com_taracuerdos)...');
            $etl->migrarTarifasAcuerdos($chunk);
            $this->mostrarResultado($etl->getReporte(), 'tarifas_acuerdos');
        }

        // Pivote multi-entidad: requiere usuarios + entidades ya migrados
        if (! $solo) {
            $this->info('Sembrando pivote entidad_user...');
            $etl->sembrarPivoteEntidades();
            $this->mostrarResultado($etl->getReporte(), 'entidad_user');
        }

        // Orden organizativa: OFICINA CENTRAL como matriz y EIDEL SUPERADMIN
        // (idempotente, se re-aplica en cada corrida ETL)
        if (! $solo) {
            $this->info('Aplicando jerarquía de entidades (OFICINA CENTRAL = matriz)...');
            $etl->migrarJerarquiaEntidades();
            $this->mostrarResultado($etl->getReporte(), 'jerarquia_entidades');
        }

        $this->newLine();
        $this->info('ETL finalizado. Ejecute php artisan emcarga:etl --validar para verificar conteos.');

        // Salva total automática tras un ETL exitoso (punto de restauración).
        // Se omite con --no-salva o cuando ya se reinició la BD con --no-fresh ausente
        // (en ese caso migrate:fresh partió de cero y la salva previa ya existe).
        if (! $this->option('no-salva')) {
            try {
                $archivo = app(DatabaseBackupService::class)->salvar('etl');
                $this->info('✔ Salva total tras ETL: '.basename($archivo));
            } catch (\RuntimeException $e) {
                $this->warn('⚠ No se pudo crear la salva tras el ETL: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function mostrarValidacion(EtlService $etl): int
    {
        $filas = [];

        foreach ($etl->validar() as $tabla => $conteos) {
            if (in_array($tabla, config('etl.excluir_datos', []))) {
                $estado = '<fg=yellow>SIN DATOS</>';
            } else {
                $estado = $conteos['legacy'] === $conteos['nueva'] ? '<fg=green>OK</>' : '<fg=red>DIFIERE</>';
            }
            $filas[] = [$tabla, $conteos['legacy'], $conteos['nueva'], $estado];
        }

        $this->table(['Tabla', 'Legacy', 'Nueva', 'Estado'], $filas);

        return self::SUCCESS;
    }

    private function mostrarResultado(array $reporte, ?string $tabla = null): void
    {
        foreach ($reporte as $nombre => $datos) {
            if ($tabla && $nombre !== $tabla) {
                continue;
            }

            $this->line("  <fg=cyan>{$nombre}</>: {$datos['nueva']}/{$datos['legacy']} registros");

            foreach (array_slice($datos['avisos'], 0, 10) as $aviso) {
                $this->warn("  ⚠ {$aviso}");
            }

            if (count($datos['avisos']) > 10) {
                $this->warn('  ⚠ ... y '.(count($datos['avisos']) - 10).' avisos más');
            }
        }
    }
}
