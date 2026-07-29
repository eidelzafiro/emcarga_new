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
            ['label' => 'Flota', 'icon' => 'pi pi-truck', 'route' => null, 'permission' => null, 'orden' => 3],
            ['label' => 'Taller', 'icon' => 'pi pi-cog', 'route' => 'taller.index', 'permission' => 'taller.ver', 'orden' => 4],
            ['label' => 'Comercial', 'icon' => 'pi pi-briefcase', 'route' => null, 'permission' => null, 'orden' => 5],
            ['label' => 'Facturación', 'icon' => 'pi pi-file-invoice', 'route' => null, 'permission' => null, 'orden' => 6],
            ['label' => 'RRHH', 'icon' => 'pi pi-users', 'route' => null, 'permission' => null, 'orden' => 7],
            ['label' => 'Contabilidad', 'icon' => 'pi pi-calculator', 'route' => null, 'permission' => null, 'orden' => 8],
            ['label' => 'Administración', 'icon' => 'pi pi-shield', 'route' => null, 'permission' => null, 'orden' => 9],
            ['label' => 'Catálogos', 'icon' => 'pi pi-book', 'route' => null, 'permission' => null, 'orden' => 10],
            ['label' => 'Reportes', 'icon' => 'pi pi-chart-bar', 'route' => null, 'permission' => null, 'orden' => 11],
            // ['label' => 'Pizarra', 'icon' => 'pi pi-th-large', 'route' => 'pizarra.index', 'permission' => 'pizarra.ver', 'orden' => 10],
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
            // Flota
            ['parent' => 'Flota', 'label' => 'Vehículos', 'route' => 'tractivos.index', 'permission' => 'tractivos.ver', 'orden' => 1],
            ['parent' => 'Flota', 'label' => 'Motores', 'route' => 'motores.index', 'permission' => 'motores.ver', 'orden' => 2],
            ['parent' => 'Flota', 'label' => 'Cajas', 'route' => 'cajas.index', 'permission' => 'cajas.ver', 'orden' => 3],
            ['parent' => 'Flota', 'label' => 'Diferenciales', 'route' => 'diferenciales.index', 'permission' => 'diferenciales.ver', 'orden' => 4],
            ['parent' => 'Flota', 'label' => 'Baterías', 'route' => 'baterias.index', 'permission' => 'baterias.ver', 'orden' => 5],
            ['parent' => 'Flota', 'label' => 'Neumáticos', 'route' => 'neumaticos.index', 'permission' => 'neumaticos.ver', 'orden' => 6],
            ['parent' => 'Flota', 'label' => 'Lubricantes', 'route' => 'lubricantes.index', 'permission' => 'lubricantes.ver', 'orden' => 7],
            ['parent' => 'Flota', 'label' => 'Otros Agregados', 'route' => 'otros-agregados.index', 'permission' => 'otros-agregados.ver', 'orden' => 8],
            ['parent' => 'Flota', 'label' => 'Arrastres', 'route' => 'arrastres.index', 'permission' => 'arrastres.ver', 'orden' => 9],
            ['parent' => 'Flota', 'label' => 'Motivos Entrada Taller', 'route' => 'motivos-entrada-taller.index', 'permission' => 'motivos-entrada-taller.ver', 'orden' => 11],
            ['parent' => 'Flota', 'label' => 'Clasif. OT', 'route' => 'clasificaciones-ordenes-taller.index', 'permission' => 'clasificaciones-ordenes-taller.ver', 'orden' => 12],
            ['parent' => 'Flota', 'label' => 'Motivos Baja Batería', 'route' => 'motivos-baja-bateria.index', 'permission' => 'motivos-baja-bateria.ver', 'orden' => 13],
            ['parent' => 'Flota', 'label' => 'Historial Tractivos', 'route' => 'historial-tractivos.index', 'permission' => 'historial-tractivos.ver', 'orden' => 14],
            ['parent' => 'Flota', 'label' => 'Estad. Explotación', 'route' => 'estadisticas-explotacion.index', 'permission' => 'estadisticas-explotacion.ver', 'orden' => 15],

            // Catálogos (root)
            ['parent' => 'Catálogos', 'label' => 'Marcas', 'route' => 'marcas.index', 'permission' => 'marcas.ver', 'orden' => 1],
            ['parent' => 'Catálogos', 'label' => 'Modelos', 'route' => 'modelos.index', 'permission' => 'modelos.ver', 'orden' => 2],
            ['parent' => 'Catálogos', 'label' => 'Tipos Modelo', 'route' => 'tipos-modelo.index', 'permission' => 'tipos-modelo.ver', 'orden' => 3],
            ['parent' => 'Catálogos', 'label' => 'Grupos', 'route' => 'grupos.index', 'permission' => 'grupos.ver', 'orden' => 3],
            ['parent' => 'Catálogos', 'label' => 'Talleres', 'route' => 'talleres.index', 'permission' => 'talleres.ver', 'orden' => 6],
            ['parent' => 'Catálogos', 'label' => 'Naves', 'route' => 'naves.index', 'permission' => 'naves.ver', 'orden' => 7],
            ['parent' => 'Catálogos', 'label' => 'Vallas', 'route' => 'vallas.index', 'permission' => 'vallas.ver', 'orden' => 8],
            ['parent' => 'Catálogos', 'label' => 'Destinos Agregados', 'route' => 'destinos-agregados.index', 'permission' => 'destinos-agregados.ver', 'orden' => 9],
            ['parent' => 'Catálogos', 'label' => 'Medidas Neumáticos', 'route' => 'medidas-neumaticos.index', 'permission' => 'medidas-neumaticos.ver', 'orden' => 10],
            ['parent' => 'Catálogos', 'label' => 'Posiciones Neumáticos', 'route' => 'posiciones-neumaticos.index', 'permission' => 'posiciones-neumaticos.ver', 'orden' => 11],
            ['parent' => 'Catálogos', 'label' => 'Consecutivos', 'route' => 'consecutivos.index', 'permission' => 'consecutivos.ver', 'orden' => 12],
            ['parent' => 'Catálogos', 'label' => 'Embalajes', 'route' => 'embalajes.index', 'permission' => 'embalajes.ver', 'orden' => 13],
            ['parent' => 'Catálogos', 'label' => 'Navieras', 'route' => 'navieras.index', 'permission' => 'navieras.ver', 'orden' => 14],
            ['parent' => 'Catálogos', 'label' => 'Organismos', 'route' => 'organismos.index', 'permission' => 'organismos.ver', 'orden' => 15],
            ['parent' => 'Catálogos', 'label' => 'Categorías Cargo', 'route' => 'categorias-cargo.index', 'permission' => 'categorias-cargo.ver', 'orden' => 16],
            ['parent' => 'Catálogos', 'label' => 'Grupos Escala', 'route' => 'grupos-escala.index', 'permission' => 'grupos-escala.ver', 'orden' => 17],
            ['parent' => 'Catálogos', 'label' => 'Entidades', 'route' => 'entidades.index', 'permission' => 'entidades.ver', 'orden' => 18],

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
            ['parent' => 'Comercial', 'label' => 'Config. Modelo', 'route' => 'configuraciones-modelo.index', 'permission' => 'configuraciones-modelo.ver', 'orden' => 12],

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
            // ['parent' => 'Contabilidad', 'label' => 'Detalles Carga Combustible', 'route' => 'detalles-carga-combustible.index', 'permission' => 'detalles-carga-combustible.ver', 'orden' => 8],
            ['parent' => 'Contabilidad', 'label' => 'Servicentros', 'route' => 'servicentros.index', 'permission' => 'servicentros.ver', 'orden' => 9],
            ['parent' => 'Contabilidad', 'label' => 'Otros Gastos', 'route' => 'otros-gastos.index', 'permission' => 'otros-gastos.ver', 'orden' => 10],
            ['parent' => 'Contabilidad', 'label' => 'Centros Costos', 'route' => 'centros-costos.index', 'permission' => 'centros-costos.ver', 'orden' => 11],
            ['parent' => 'Contabilidad', 'label' => 'Reportes Costos', 'route' => 'reportes-costos.index', 'permission' => 'reportes-costos.ver', 'orden' => 12],

            // Administración (parent_id=8)
            ['parent' => 'Administración', 'label' => 'Usuarios', 'route' => 'usuarios.index', 'permission' => 'usuarios.ver', 'orden' => 1],
            ['parent' => 'Administración', 'label' => 'Perfiles', 'route' => 'perfiles.index', 'permission' => 'perfiles.ver', 'orden' => 2],
            ['parent' => 'Administración', 'label' => 'Entidades', 'route' => 'entidades.index', 'permission' => 'entidades.ver', 'orden' => 3],
            ['parent' => 'Administración', 'label' => 'Tarjetero', 'route' => 'catalogo.gestionar', 'permission' => 'catalogo.editar', 'orden' => 4],
            ['parent' => 'Administración', 'label' => 'Menú', 'route' => 'menu-items.index', 'permission' => 'menus.ver', 'orden' => 5],

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
