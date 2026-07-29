<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        MenuItem::firstOrCreate(
            ['route' => 'dashboard'],
            ['label' => 'Dashboard', 'icon' => 'pi pi-home', 'permission' => 'dashboard.ver', 'orden' => 1]
        );

        $flota = MenuItem::firstOrCreate(
            ['label' => 'Flota', 'parent_id' => null],
            ['icon' => 'pi pi-truck', 'route' => null, 'permission' => null, 'orden' => 3]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tractivos.index'],
            ['label' => 'Vehículos', 'icon' => 'pi pi-truck', 'permission' => 'tractivos.ver', 'orden' => 1, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'motores.index'],
            ['label' => 'Motores', 'icon' => 'pi pi-cog', 'permission' => 'motores.ver', 'orden' => 2, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'cajas.index'],
            ['label' => 'Cajas', 'icon' => 'pi pi-cog', 'permission' => 'cajas.ver', 'orden' => 3, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'diferenciales.index'],
            ['label' => 'Diferenciales', 'icon' => 'pi pi-cog', 'permission' => 'diferenciales.ver', 'orden' => 4, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'baterias.index'],
            ['label' => 'Baterías', 'icon' => 'pi pi-bolt', 'permission' => 'baterias.ver', 'orden' => 5, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'neumaticos.index'],
            ['label' => 'Neumáticos', 'icon' => 'pi pi-cog', 'permission' => 'neumaticos.ver', 'orden' => 6, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'lubricantes.index'],
            ['label' => 'Lubricantes', 'icon' => 'pi pi-eye-dropper', 'permission' => 'lubricantes.ver', 'orden' => 7, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'otros-agregados.index'],
            ['label' => 'Otros Agregados', 'icon' => 'pi pi-cog', 'permission' => 'otros-agregados.ver', 'orden' => 8, 'parent_id' => $flota->id]
        );

        // Flota - Tablas faltantes
        MenuItem::firstOrCreate(
            ['route' => 'arrastres.index'],
            ['label' => 'Arrastres', 'icon' => 'pi pi-truck', 'permission' => 'arrastres.ver', 'orden' => 10, 'parent_id' => $flota->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'motivos-entrada-taller.index'],
            ['label' => 'Motivos Entrada Taller', 'icon' => 'pi pi-sign-in', 'permission' => 'motivos-entrada-taller.ver', 'orden' => 11, 'parent_id' => $flota->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'clasificaciones-ordenes-taller.index'],
            ['label' => 'Clasif. OT', 'icon' => 'pi pi-tags', 'permission' => 'clasificaciones-ordenes-taller.ver', 'orden' => 13, 'parent_id' => $flota->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'motivos-baja-bateria.index'],
            ['label' => 'Motivos Baja Batería', 'icon' => 'pi pi-bolt', 'permission' => 'motivos-baja-bateria.ver', 'orden' => 16, 'parent_id' => $flota->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'historial-tractivos.index'],
            ['label' => 'Historial Tractivos', 'icon' => 'pi pi-history', 'permission' => 'historial-tractivos.ver', 'orden' => 21, 'parent_id' => $flota->id]
        );
        
        MenuItem::firstOrCreate(
            ['route' => 'choferes.index'],
            ['label' => 'Choferes', 'icon' => 'pi pi-user', 'permission' => 'choferes.ver', 'orden' => 32, 'parent_id' => $flota->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'estadisticas-explotacion.index'],
            ['label' => 'Estad. Explotación', 'icon' => 'pi pi-chart-line', 'permission' => 'estadisticas-explotacion.ver', 'orden' => 33, 'parent_id' => $flota->id]
        );
        
        MenuItem::firstOrCreate(
            ['route' => 'taller.index'],
            ['label' => 'Taller', 'icon' => 'pi pi-wrench', 'permission' => 'taller.ver', 'orden' => 4]
        );

        $comercial = MenuItem::firstOrCreate(
            ['label' => 'Comercial', 'parent_id' => null],
            ['icon' => 'pi pi-briefcase', 'route' => null, 'permission' => null, 'orden' => 5]
        );

        MenuItem::firstOrCreate(
            ['route' => 'clientes.index'],
            ['label' => 'Clientes', 'icon' => 'pi pi-users', 'permission' => 'clientes.ver', 'orden' => 1, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'lugares.index'],
            ['label' => 'Lugares', 'icon' => 'pi pi-map-marker', 'permission' => 'lugares.ver', 'orden' => 2, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'distancias.index'],
            ['label' => 'Distancias', 'icon' => 'pi pi-arrows-alt', 'permission' => 'distancias.ver', 'orden' => 3, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'acuerdos.index'],
            ['label' => 'Acuerdos', 'icon' => 'pi pi-file', 'permission' => 'acuerdos.ver', 'orden' => 4, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'solicitudes.index'],
            ['label' => 'Solicitudes', 'icon' => 'pi pi-envelope', 'permission' => 'solicitudes.ver', 'orden' => 5, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'giros.index'],
            ['label' => 'Cartas Porte', 'icon' => 'pi pi-file', 'permission' => 'giros.ver', 'orden' => 6, 'parent_id' => $comercial->id]
        );

        // Comercial - Tablas faltantes
        MenuItem::firstOrCreate(
            ['route' => 'alertas.index'],
            ['label' => 'Alertas', 'icon' => 'pi pi-bell', 'permission' => 'alertas.ver', 'orden' => 7, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tarifas.index'],
            ['label' => 'Tarifas', 'icon' => 'pi pi-dollar', 'permission' => 'tarifas.ver', 'orden' => 8, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'demandas.index'],
            ['label' => 'Demandas', 'icon' => 'pi pi-chart-bar', 'permission' => 'demandas.ver', 'orden' => 9, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'indicadores.index'],
            ['label' => 'Indicadores', 'icon' => 'pi pi-chart-line', 'permission' => 'indicadores.ver', 'orden' => 10, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'pizarra-tractivos.index'],
            ['label' => 'Pizarra Tractivos', 'icon' => 'pi pi-th-large', 'permission' => 'pizarra-tractivos.ver', 'orden' => 11, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'turnos-comerciales.index'],
            ['label' => 'Turnos', 'icon' => 'pi pi-clock', 'permission' => 'turnos-comerciales.ver', 'orden' => 12, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'otros-ingresos-pre.index'],
            ['label' => 'Otros Ingresos Pre', 'icon' => 'pi pi-plus-circle', 'permission' => 'otros-ingresos-pre.ver', 'orden' => 14, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'configuraciones-modelo.index'],
            ['label' => 'Config. Modelo', 'icon' => 'pi pi-sliders-h', 'permission' => 'configuraciones-modelo.ver', 'orden' => 18, 'parent_id' => $comercial->id]
        );


        // Comercial - Tablas faltantes parte 2
        MenuItem::firstOrCreate(
            ['route' => 'contenedores.index'],
            ['label' => 'Contenedores', 'icon' => 'pi pi-box', 'permission' => 'contenedores.ver', 'orden' => 22, 'parent_id' => $comercial->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'categorias-productos.index'],
            ['label' => 'Categorías Productos', 'icon' => 'pi pi-tags', 'permission' => 'categorias-productos.ver', 'orden' => 23, 'parent_id' => $comercial->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'devoluciones.index'],
            ['label' => 'Devoluciones', 'icon' => 'pi pi-undo', 'permission' => 'devoluciones.ver', 'orden' => 26, 'parent_id' => $comercial->id]
        );

        $facturacion = MenuItem::firstOrCreate(
            ['label' => 'Facturación', 'parent_id' => null],
            ['icon' => 'pi pi-file', 'route' => null, 'permission' => null, 'orden' => 6]
        );

        MenuItem::firstOrCreate(
            ['route' => 'facturas.index'],
            ['label' => 'Facturas', 'icon' => 'pi pi-file', 'permission' => 'facturas.ver', 'orden' => 1, 'parent_id' => $facturacion->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'prefacturas.index'],
            ['label' => 'Prefacturas', 'icon' => 'pi pi-file-edit', 'permission' => 'prefacturas.ver', 'orden' => 2, 'parent_id' => $facturacion->id]
        );

        $rrhh = MenuItem::firstOrCreate(
            ['label' => 'RRHH', 'parent_id' => null],
            ['icon' => 'pi pi-users', 'route' => null, 'permission' => null, 'orden' => 7]
        );

        MenuItem::firstOrCreate(
            ['route' => 'bolsa.index'],
            ['label' => 'Bolsa', 'icon' => 'pi pi-user', 'permission' => 'bolsa.ver', 'orden' => 1, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'plantilla.index'],
            ['label' => 'Plantilla', 'icon' => 'pi pi-table', 'permission' => 'plantilla.ver', 'orden' => 2, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'historial-movimientos.index'],
            ['label' => 'Historial', 'icon' => 'pi pi-history', 'permission' => 'historial-movimientos.ver', 'orden' => 3, 'parent_id' => $rrhh->id]
        );

        // RRHH - Tablas faltantes (Fase 5.5 parte 3)
        MenuItem::firstOrCreate(
            ['route' => 'salarios.index'],
            ['label' => 'Salarios', 'icon' => 'pi pi-dollar', 'permission' => 'salarios.ver', 'orden' => 10, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'salarios-administrativos.index'],
            ['label' => 'Salarios Admin.', 'icon' => 'pi pi-briefcase', 'permission' => 'salarios-administrativos.ver', 'orden' => 11, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'osdes.index'],
            ['label' => 'OSDEs', 'icon' => 'pi pi-building', 'permission' => 'osdes.ver', 'orden' => 14, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'firmas.index'],
            ['label' => 'Firmas', 'icon' => 'pi pi-file-edit', 'permission' => 'firmas.ver', 'orden' => 15, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'fondos-tiempo.index'],
            ['label' => 'Fondos Tiempo', 'icon' => 'pi pi-clock', 'permission' => 'fondos-tiempo.ver', 'orden' => 17, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'medios-proteccion.index'],
            ['label' => 'Medios Protección', 'icon' => 'pi pi-shield', 'permission' => 'medios-proteccion.ver', 'orden' => 18, 'parent_id' => $rrhh->id]
        );

        // RRHH - Tablas faltantes parte 2
        MenuItem::firstOrCreate(
            ['route' => 'centros-costos.index'],
            ['label' => 'Centros Costo', 'icon' => 'pi pi-dollar', 'permission' => 'centros-costos.ver', 'orden' => 38, 'parent_id' => $rrhh->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'pagos-adicionales-cargo.index'],
            ['label' => 'Pagos Adic. Cargo', 'icon' => 'pi pi-money-bill', 'permission' => 'pagos-adicionales-cargo.ver', 'orden' => 43, 'parent_id' => $rrhh->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'empleados.index'],
            ['label' => 'Empleados', 'icon' => 'pi pi-users', 'permission' => 'empleados.ver', 'orden' => 47, 'parent_id' => $rrhh->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'descuentos-empleados.index'],
            ['label' => 'Desc. Empleados', 'icon' => 'pi pi-minus-circle', 'permission' => 'descuentos-empleados.ver', 'orden' => 48, 'parent_id' => $rrhh->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'vacaciones.index'],
            ['label' => 'Vacaciones', 'icon' => 'pi pi-calendar', 'permission' => 'vacaciones.ver', 'orden' => 49, 'parent_id' => $rrhh->id]
        );

        $contabilidad = MenuItem::firstOrCreate(
            ['label' => 'Contabilidad', 'parent_id' => null],
            ['icon' => 'pi pi-book', 'route' => null, 'permission' => null, 'orden' => 8]
        );

        MenuItem::firstOrCreate(
            ['route' => 'conciliaciones.index'],
            ['label' => 'Conciliaciones', 'icon' => 'pi pi-check-circle', 'permission' => 'conciliaciones.ver', 'orden' => 1, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'otros-gastos.index'],
            ['label' => 'Otros Gastos', 'icon' => 'pi pi-dollar', 'permission' => 'otros-gastos.ver', 'orden' => 3, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'combustible-cargas.index'],
            ['label' => 'Carga Combustible', 'icon' => 'pi pi-gauge', 'permission' => 'combustible-cargas.ver', 'orden' => 4, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'combustible-descargas.index'],
            ['label' => 'Descarga Combustible', 'icon' => 'pi pi-download', 'permission' => 'combustible-descargas.ver', 'orden' => 5, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'inventario.index'],
            ['label' => 'Inventario', 'icon' => 'pi pi-box', 'permission' => 'inventario.ver', 'orden' => 6, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'vales.index'],
            ['label' => 'Vales', 'icon' => 'pi pi-ticket', 'permission' => 'vales.ver', 'orden' => 7, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'servicentros.index'],
            ['label' => 'Servicentros', 'icon' => 'pi pi-map', 'permission' => 'servicentros.ver', 'orden' => 8, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'firmas-autorizadas.index'],
            ['label' => 'Firmas Autorizadas', 'icon' => 'pi pi-pencil', 'permission' => 'firmas-autorizadas.ver', 'orden' => 10, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'reportes-costos.index'],
            ['label' => 'Reportes Costos', 'icon' => 'pi pi-chart-line', 'permission' => 'reportes-costos.ver', 'orden' => 11, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'estados-tarjetas.index'],
            ['label' => 'Estados Tarjetas', 'icon' => 'pi pi-credit-card', 'permission' => 'estados-tarjetas.ver', 'orden' => 12, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'combustibles-lubricantes.index'],
            ['label' => 'Comb. Lubricantes', 'icon' => 'pi pi-eye-dropper', 'permission' => 'combustibles-lubricantes.ver', 'orden' => 14, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'pagos.index'],
            ['label' => 'Pagos', 'icon' => 'pi pi-money-bill', 'permission' => 'pagos.ver', 'orden' => 15, 'parent_id' => $contabilidad->id]
        );
        MenuItem::firstOrCreate(
            ['route' => 'elementos-gasto.index'],
            ['label' => 'Elementos Gasto', 'icon' => 'pi pi-receipt', 'permission' => 'elementos-gasto.ver', 'orden' => 16, 'parent_id' => $contabilidad->id]
        );

        $catalogos = MenuItem::firstOrCreate(
            ['label' => 'Catálogos', 'parent_id' => null],
            ['icon' => 'pi pi-bookmark', 'route' => null, 'permission' => null, 'orden' => 9]
        );

        $catItems = [
            ['marcas.index', 'Marcas', 'pi pi-tag', 'marcas.ver', 1],
            ['modelos.index', 'Modelos', 'pi pi-cog', 'modelos.ver', 2],
            ['grupos.index', 'Grupos', 'pi pi-bullseye', 'grupos.ver', 3],
            ['talleres.index', 'Talleres', 'pi pi-wrench', 'talleres.ver', 6],
            ['naves.index', 'Naves', 'pi pi-building', 'naves.ver', 7],
            ['vallas.index', 'Vallas', 'pi pi-th-large', 'vallas.ver', 8],
            ['destinos-agregados.index', 'Destinos Agregados', 'pi pi-map', 'destinos-agregados.ver', 9],
            ['medidas-neumaticos.index', 'Medidas Neumáticos', 'pi pi-arrows-h', 'medidas-neumaticos.ver', 10],
            ['posiciones-neumaticos.index', 'Posiciones Neumáticos', 'pi pi-arrows-alt', 'posiciones-neumaticos.ver', 15],
            ['consecutivos.index', 'Consecutivos', 'pi pi-list', 'consecutivos.ver', 16],
            ['embalajes.index', 'Embalajes', 'pi pi-box', 'embalajes.ver', 19],
            ['navieras.index', 'Navieras', 'pi pi-globe', 'navieras.ver', 21],
            ['organismos.index', 'Organismos', 'pi pi-building', 'organismos.ver', 22],
            ['categorias-cargo.index', 'Categorías Cargo', 'pi pi-tags', 'categorias-cargo.ver', 23],
            ['grupos-escala.index', 'Grupos Escala', 'pi pi-chart-bar', 'grupos-escala.ver', 24],
            ['entidades.index', 'Entidades', 'pi pi-building', 'entidades.ver', 25],
        ];

        foreach ($catItems as [$route, $label, $icon, $perm, $orden]) {
            MenuItem::firstOrCreate(
                ['route' => $route],
                ['label' => $label, 'icon' => $icon, 'permission' => $perm, 'orden' => $orden, 'parent_id' => $catalogos->id]
            );
        }

        $reportes = MenuItem::firstOrCreate(
            ['label' => 'Reportes', 'parent_id' => null],
            ['icon' => 'pi pi-file-pdf', 'route' => null, 'permission' => 'reportes.ver', 'orden' => 10]
        );

        MenuItem::firstOrCreate(
            ['route' => 'reportes.marcas'],
            ['label' => 'Listado de Marcas', 'icon' => 'pi pi-tag', 'permission' => 'reportes.generar', 'orden' => 1, 'parent_id' => $reportes->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'reportes.modelos'],
            ['label' => 'Listado de Modelos', 'icon' => 'pi pi-cog', 'permission' => 'reportes.generar', 'orden' => 2, 'parent_id' => $reportes->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'reportes.paises'],
            ['label' => 'Listado de Países', 'icon' => 'pi pi-globe', 'permission' => 'reportes.generar', 'orden' => 3, 'parent_id' => $reportes->id]
        );

        $administracion = MenuItem::firstOrCreate(
            ['label' => 'Administración', 'parent_id' => null],
            ['icon' => 'pi pi-cog', 'route' => null, 'permission' => null, 'orden' => 90]
        );

        MenuItem::firstOrCreate(
            ['route' => 'usuarios.index'],
            ['label' => 'Usuarios', 'icon' => 'pi pi-users', 'permission' => 'usuarios.ver', 'orden' => 1, 'parent_id' => $administracion->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'perfiles.index'],
            ['label' => 'Perfiles', 'icon' => 'pi pi-shield', 'permission' => 'perfiles.ver', 'orden' => 2, 'parent_id' => $administracion->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'menu-items.index'],
            ['label' => 'Menús', 'icon' => 'pi pi-bars', 'permission' => 'menus.ver', 'orden' => 3, 'parent_id' => $administracion->id]
        );
    }
}
