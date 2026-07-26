<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('menu_items')->truncate();

        $now = now();

        // Padres (agrupadores sin ruta)
        $padres = [
            ['label' => 'Dashboard', 'icon' => 'pi pi-home', 'route' => 'dashboard', 'permission' => 'dashboard.ver', 'orden' => 1],
            ['label' => 'Técnica', 'icon' => 'pi pi-wrench', 'route' => null, 'permission' => null, 'orden' => 2],
            ['label' => 'Taller', 'icon' => 'pi pi-cog', 'route' => 'taller.index', 'permission' => 'taller.ver', 'orden' => 3],
            ['label' => 'Comercial', 'icon' => 'pi pi-briefcase', 'route' => null, 'permission' => null, 'orden' => 4],
            ['label' => 'Facturación', 'icon' => 'pi pi-file-invoice', 'route' => null, 'permission' => null, 'orden' => 5],
            ['label' => 'RRHH', 'icon' => 'pi pi-users', 'route' => null, 'permission' => null, 'orden' => 6],
            ['label' => 'Contabilidad', 'icon' => 'pi pi-calculator', 'route' => null, 'permission' => null, 'orden' => 7],
            ['label' => 'Administración', 'icon' => 'pi pi-shield', 'route' => null, 'permission' => null, 'orden' => 8],
            ['label' => 'Reportes', 'icon' => 'pi pi-chart-bar', 'route' => null, 'permission' => null, 'orden' => 9],
            ['label' => 'Pizarra', 'icon' => 'pi pi-th-large', 'route' => 'pizarra.index', 'permission' => 'pizarra.ver', 'orden' => 10],
        ];

        foreach ($padres as $i => $p) {
            $padres[$i]['id'] = $i + 1;
            $padres[$i]['parent_id'] = null;
            $padres[$i]['created_at'] = $now;
            $padres[$i]['updated_at'] = $now;
        }

        DB::table('menu_items')->insert(array_map(fn ($p) => [
            'id' => $p['id'],
            'parent_id' => $p['parent_id'],
            'label' => $p['label'],
            'icon' => $p['icon'],
            'route' => $p['route'],
            'permission' => $p['permission'],
            'orden' => $p['orden'],
            'activo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $padres));

        $padresIdx = collect($padres)->keyBy('label');

        // Hijos por módulo
        $hijos = [
            // Técnica (parent_id=2)
            ['parent' => 'Técnica', 'label' => 'Tractivos', 'route' => 'tractivos.index', 'permission' => 'tractivos.ver', 'orden' => 1],
            ['parent' => 'Técnica', 'label' => 'Motores', 'route' => 'motores.index', 'permission' => 'motores.ver', 'orden' => 2],
            ['parent' => 'Técnica', 'label' => 'Cajas', 'route' => 'cajas.index', 'permission' => 'cajas.ver', 'orden' => 3],
            ['parent' => 'Técnica', 'label' => 'Diferenciales', 'route' => 'diferenciales.index', 'permission' => 'diferenciales.ver', 'orden' => 4],
            ['parent' => 'Técnica', 'label' => 'Baterías', 'route' => 'baterias.index', 'permission' => 'baterias.ver', 'orden' => 5],
            ['parent' => 'Técnica', 'label' => 'Neumáticos', 'route' => 'neumaticos.index', 'permission' => 'neumaticos.ver', 'orden' => 6],
            ['parent' => 'Técnica', 'label' => 'Lubricantes', 'route' => 'lubricantes.index', 'permission' => 'lubricantes.ver', 'orden' => 7],
            ['parent' => 'Técnica', 'label' => 'Otros Agregados', 'route' => 'otros-agregados.index', 'permission' => 'otros-agregados.ver', 'orden' => 8],
            ['parent' => 'Técnica', 'label' => 'Energía', 'route' => 'energia.index', 'permission' => 'energia.ver', 'orden' => 9],
            ['parent' => 'Técnica', 'label' => 'Arrastres', 'route' => 'arrastres.index', 'permission' => 'arrastres.ver', 'orden' => 10],
            ['parent' => 'Técnica', 'label' => 'Balances Eléctricos', 'route' => 'balances-electricos.index', 'permission' => 'balances-electricos.ver', 'orden' => 11],
            ['parent' => 'Técnica', 'label' => 'Historial Tractivos', 'route' => 'historial-tractivos.index', 'permission' => 'historial-tractivos.ver', 'orden' => 12],
            ['parent' => 'Técnica', 'label' => 'Tarjetero', 'route' => 'tarjetero.index', 'permission' => 'tarjetero.ver', 'orden' => 13],

            // Comercial (parent_id=4)
            ['parent' => 'Comercial', 'label' => 'Clientes', 'route' => 'clientes.index', 'permission' => 'clientes.ver', 'orden' => 1],
            ['parent' => 'Comercial', 'label' => 'Lugares', 'route' => 'lugares.index', 'permission' => 'lugares.ver', 'orden' => 2],
            ['parent' => 'Comercial', 'label' => 'Distancias', 'route' => 'distancias.index', 'permission' => 'distancias.ver', 'orden' => 3],
            ['parent' => 'Comercial', 'label' => 'Acuerdos', 'route' => 'acuerdos.index', 'permission' => 'acuerdos.ver', 'orden' => 4],
            ['parent' => 'Comercial', 'label' => 'Solicitudes', 'route' => 'solicitudes.index', 'permission' => 'solicitudes.ver', 'orden' => 5],
            ['parent' => 'Comercial', 'label' => 'Giros', 'route' => 'giros.index', 'permission' => 'giros.ver', 'orden' => 6],
            ['parent' => 'Comercial', 'label' => 'Tarifas', 'route' => 'tarifas.index', 'permission' => 'tarifas.ver', 'orden' => 7],
            ['parent' => 'Comercial', 'label' => 'Turnos Comerciales', 'route' => 'turnos-comerciales.index', 'permission' => 'turnos-comerciales.ver', 'orden' => 8],
            ['parent' => 'Comercial', 'label' => 'Alertas', 'route' => 'alertas.index', 'permission' => 'alertas.ver', 'orden' => 9],
            ['parent' => 'Comercial', 'label' => 'Indicadores', 'route' => 'indicadores.index', 'permission' => 'indicadores.ver', 'orden' => 10],
            ['parent' => 'Comercial', 'label' => 'Demandas', 'route' => 'demandas.index', 'permission' => 'demandas.ver', 'orden' => 11],

            // Facturación (parent_id=5)
            ['parent' => 'Facturación', 'label' => 'Facturas', 'route' => 'facturas.index', 'permission' => 'facturas.ver', 'orden' => 1],
            ['parent' => 'Facturación', 'label' => 'Prefacturas', 'route' => 'prefacturas.index', 'permission' => 'prefacturas.ver', 'orden' => 2],
            ['parent' => 'Facturación', 'label' => 'Aforos Pendientes', 'route' => 'aforos.pendientes', 'permission' => 'facturas.ver', 'orden' => 3],

            // RRHH (parent_id=6)
            ['parent' => 'RRHH', 'label' => 'Bolsa', 'route' => 'bolsa.index', 'permission' => 'bolsa.ver', 'orden' => 1],
            ['parent' => 'RRHH', 'label' => 'Plantilla', 'route' => 'plantilla.index', 'permission' => 'plantilla.ver', 'orden' => 2],
            ['parent' => 'RRHH', 'label' => 'Historial Movimientos', 'route' => 'historial-movimientos.index', 'permission' => 'historial-movimientos.ver', 'orden' => 3],
            ['parent' => 'RRHH', 'label' => 'Salarios', 'route' => 'salarios.index', 'permission' => 'salarios.ver', 'orden' => 4],
            ['parent' => 'RRHH', 'label' => 'Salarios Administrativos', 'route' => 'salarios-administrativos.index', 'permission' => 'salarios-administrativos.ver', 'orden' => 5],
            ['parent' => 'RRHH', 'label' => 'Vacaciones', 'route' => 'vacaciones.index', 'permission' => 'vacaciones.ver', 'orden' => 6],
            ['parent' => 'RRHH', 'label' => 'Empleados', 'route' => 'empleados.index', 'permission' => 'empleados.ver', 'orden' => 7],
            ['parent' => 'RRHH', 'label' => 'Choferes', 'route' => 'choferes.index', 'permission' => 'choferes.ver', 'orden' => 8],
            ['parent' => 'RRHH', 'label' => 'Descuentos Empleados', 'route' => 'descuentos-empleados.index', 'permission' => 'descuentos-empleados.ver', 'orden' => 9],
            ['parent' => 'RRHH', 'label' => 'Devoluciones', 'route' => 'devoluciones.index', 'permission' => 'devoluciones.ver', 'orden' => 10],

            // Contabilidad (parent_id=7)
            ['parent' => 'Contabilidad', 'label' => 'Conciliaciones', 'route' => 'conciliaciones.index', 'permission' => 'conciliaciones.ver', 'orden' => 1],
            ['parent' => 'Contabilidad', 'label' => 'Inventario', 'route' => 'inventario.index', 'permission' => 'inventario.ver', 'orden' => 2],
            ['parent' => 'Contabilidad', 'label' => 'Vales', 'route' => 'vales.index', 'permission' => 'vales.ver', 'orden' => 3],
            ['parent' => 'Contabilidad', 'label' => 'Pagos', 'route' => 'pagos.index', 'permission' => 'pagos.ver', 'orden' => 4],
            ['parent' => 'Contabilidad', 'label' => 'Combustible Cargas', 'route' => 'combustible-cargas.index', 'permission' => 'combustible-cargas.ver', 'orden' => 5],
            ['parent' => 'Contabilidad', 'label' => 'Combustible Descargas', 'route' => 'combustible-descargas.index', 'permission' => 'combustible-descargas.ver', 'orden' => 6],
            ['parent' => 'Contabilidad', 'label' => 'Combustibles Lubricantes', 'route' => 'combustibles-lubricantes.index', 'permission' => 'combustibles-lubricantes.ver', 'orden' => 7],
            ['parent' => 'Contabilidad', 'label' => 'Detalles Carga Combustible', 'route' => 'detalles-carga-combustible.index', 'permission' => 'detalles-carga-combustible.ver', 'orden' => 8],
            ['parent' => 'Contabilidad', 'label' => 'Servicentros', 'route' => 'servicentros.index', 'permission' => 'servicentros.ver', 'orden' => 9],
            ['parent' => 'Contabilidad', 'label' => 'Otros Gastos', 'route' => 'otros-gastos.index', 'permission' => 'otros-gastos.ver', 'orden' => 10],
            ['parent' => 'Contabilidad', 'label' => 'Centros Costos', 'route' => 'centros-costos.index', 'permission' => 'centros-costos.ver', 'orden' => 11],
            ['parent' => 'Contabilidad', 'label' => 'Reportes Costos', 'route' => 'reportes-costos.index', 'permission' => 'reportes-costos.ver', 'orden' => 12],

            // Administración (parent_id=8)
            ['parent' => 'Administración', 'label' => 'Usuarios', 'route' => 'usuarios.index', 'permission' => 'usuarios.ver', 'orden' => 1],
            ['parent' => 'Administración', 'label' => 'Perfiles', 'route' => 'perfiles.index', 'permission' => 'perfiles.ver', 'orden' => 2],

            // Reportes (parent_id=9)
            ['parent' => 'Reportes', 'label' => 'Marcas', 'route' => 'reportes.marcas', 'permission' => 'reportes.generar', 'orden' => 1],
            ['parent' => 'Reportes', 'label' => 'Modelos', 'route' => 'reportes.modelos', 'permission' => 'reportes.generar', 'orden' => 2],
            ['parent' => 'Reportes', 'label' => 'Salario Prenómina', 'route' => 'reportes.salario-prenomina', 'permission' => 'reportes.generar', 'orden' => 3],
            ['parent' => 'Reportes', 'label' => 'Salario Choferes', 'route' => 'reportes.salario-choferes', 'permission' => 'reportes.generar', 'orden' => 4],
        ];

        $inserts = [];
        $id = count($padres) + 1;

        foreach ($hijos as $h) {
            $parentId = $padresIdx[$h['parent']]['id'];
            $inserts[] = [
                'id' => $id++,
                'parent_id' => $parentId,
                'label' => $h['label'],
                'icon' => 'pi pi-circle-fill',
                'route' => $h['route'],
                'permission' => $h['permission'],
                'orden' => $h['orden'],
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('menu_items')->insert($inserts);
    }
}
