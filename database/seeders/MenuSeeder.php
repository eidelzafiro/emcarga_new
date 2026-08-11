<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Usar updateOrCreate para preservar cambios manuales hechos via UI.

        $padres = [
            ['Dashboard', 'pi pi-home', 'dashboard', 'dashboard.ver', 1],
            ['Técnica', 'pi pi-wrench', null, null, 2],
            ['Flota', 'pi pi-truck', null, null, 3],
            ['Taller', 'pi pi-cog', 'taller.index', 'taller.ver', 4],
            ['Comercial', 'pi pi-briefcase', null, null, 5],
            ['Facturación', 'pi pi-file-invoice', null, null, 6],
            ['RRHH', 'pi pi-users', null, null, 7],
            ['Contabilidad', 'pi pi-calculator', null, null, 8],
            ['Administración', 'pi pi-shield', null, null, 9],
            ['Catálogos', 'pi pi-book', null, null, 10],
            ['Reportes', 'pi pi-chart-bar', null, null, 11],
        ];

        $parentIds = [];
        foreach ($padres as [$label, $icon, $route, $perm, $orden]) {
            $item = MenuItem::updateOrCreate(
                ['label' => $label, 'parent_id' => null],
                ['icon' => $icon, 'route' => $route, 'permission' => $perm, 'orden' => $orden, 'activo' => true]
            );
            $parentIds[$label] = $item->id;
        }

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
            ['parent' => 'Flota', 'label' => 'Arrastres', 'route' => 'arrastres.index', 'permission' => 'arrastres.ver', 'orden' => 10],
            ['parent' => 'Flota', 'label' => 'Motivos Entrada Taller', 'route' => 'motivos-entrada-taller.index', 'permission' => 'motivos-entrada-taller.ver', 'orden' => 11],
            ['parent' => 'Flota', 'label' => 'Clasif. OT', 'route' => 'clasificaciones-ordenes-taller.index', 'permission' => 'clasificaciones-ordenes-taller.ver', 'orden' => 13],
            ['parent' => 'Flota', 'label' => 'Motivos Baja Batería', 'route' => 'motivos-baja-bateria.index', 'permission' => 'motivos-baja-bateria.ver', 'orden' => 16],
            ['parent' => 'Flota', 'label' => 'Historial Tractivos', 'route' => 'historial-tractivos.index', 'permission' => 'historial-tractivos.ver', 'orden' => 21],
            ['parent' => 'Flota', 'label' => 'Estad. Explotación', 'route' => 'estadisticas-explotacion.index', 'permission' => 'estadisticas-explotacion.ver', 'orden' => 33],
            // Flota - extras
            ['parent' => 'Flota', 'label' => 'Choferes', 'route' => 'choferes.index', 'permission' => 'choferes.ver', 'orden' => 32],

            // Catálogos
            ['parent' => 'Catálogos', 'label' => 'Marcas', 'route' => 'marcas.index', 'permission' => 'marcas.ver', 'orden' => 1],
            ['parent' => 'Catálogos', 'label' => 'Modelos', 'route' => 'modelos.index', 'permission' => 'modelos.ver', 'orden' => 2],
            ['parent' => 'Catálogos', 'label' => 'Grupos', 'route' => 'grupos.index', 'permission' => 'grupos.ver', 'orden' => 3],
            ['parent' => 'Catálogos', 'label' => 'Talleres', 'route' => 'talleres.index', 'permission' => 'talleres.ver', 'orden' => 6],
            ['parent' => 'Catálogos', 'label' => 'Naves', 'route' => 'naves.index', 'permission' => 'naves.ver', 'orden' => 7],
            ['parent' => 'Catálogos', 'label' => 'Vallas', 'route' => 'vallas.index', 'permission' => 'vallas.ver', 'orden' => 8],
            ['parent' => 'Catálogos', 'label' => 'Destinos Agregados', 'route' => 'destinos-agregados.index', 'permission' => 'destinos-agregados.ver', 'orden' => 9],
            ['parent' => 'Catálogos', 'label' => 'Medidas Neumáticos', 'route' => 'medidas-neumaticos.index', 'permission' => 'medidas-neumaticos.ver', 'orden' => 10],
            ['parent' => 'Catálogos', 'label' => 'Posiciones Neumáticos', 'route' => 'posiciones-neumaticos.index', 'permission' => 'posiciones-neumaticos.ver', 'orden' => 15],
            ['parent' => 'Catálogos', 'label' => 'Embalajes', 'route' => 'embalajes.index', 'permission' => 'embalajes.ver', 'orden' => 19],
            ['parent' => 'Catálogos', 'label' => 'Navieras', 'route' => 'navieras.index', 'permission' => 'navieras.ver', 'orden' => 21],
            ['parent' => 'Catálogos', 'label' => 'Organismos', 'route' => 'organismos.index', 'permission' => 'organismos.ver', 'orden' => 22],
            ['parent' => 'Catálogos', 'label' => 'Categorías Cargo', 'route' => 'categorias-cargo.index', 'permission' => 'categorias-cargo.ver', 'orden' => 23],
            ['parent' => 'Catálogos', 'label' => 'Grupos Escala', 'route' => 'grupos-escala.index', 'permission' => 'grupos-escala.ver', 'orden' => 24],
            ['parent' => 'Catálogos', 'label' => 'Cargos', 'route' => 'cargos.index', 'permission' => 'cargos.ver', 'orden' => 25],
            ['parent' => 'Catálogos', 'label' => 'Entidades', 'route' => 'entidades.index', 'permission' => 'entidades.ver', 'orden' => 26],
            ['parent' => 'Catálogos', 'label' => 'Tipos de Modelo', 'route' => 'tipos-modelo.index', 'permission' => 'tipos-modelo.ver', 'orden' => 27],

            // Comercial
            ['parent' => 'Comercial', 'label' => 'Clientes', 'route' => 'clientes.index', 'permission' => 'clientes.ver', 'orden' => 1],
            ['parent' => 'Comercial', 'label' => 'Lugares', 'route' => 'lugares.index', 'permission' => 'lugares.ver', 'orden' => 2],
            ['parent' => 'Comercial', 'label' => 'Distancias', 'route' => 'distancias.index', 'permission' => 'distancias.ver', 'orden' => 3],
            ['parent' => 'Comercial', 'label' => 'Acuerdos', 'route' => 'acuerdos.index', 'permission' => 'acuerdos.ver', 'orden' => 4],
            ['parent' => 'Comercial', 'label' => 'Solicitudes', 'route' => 'solicitudes.index', 'permission' => 'solicitudes.ver', 'orden' => 5],
            ['parent' => 'Comercial', 'label' => 'Cartas Porte', 'route' => 'carta-porte.index', 'permission' => 'carta-porte.ver', 'orden' => 6],
            ['parent' => 'Comercial', 'label' => 'Alertas', 'route' => 'alertas.index', 'permission' => 'alertas.ver', 'orden' => 7],
            ['parent' => 'Comercial', 'label' => 'Tarifas', 'route' => 'tarifas.index', 'permission' => 'tarifas.ver', 'orden' => 8],
            ['parent' => 'Comercial', 'label' => 'Demandas', 'route' => 'demandas.index', 'permission' => 'demandas.ver', 'orden' => 9],
            ['parent' => 'Comercial', 'label' => 'Indicadores', 'route' => 'indicadores.index', 'permission' => 'indicadores.ver', 'orden' => 10],
            ['parent' => 'Comercial', 'label' => 'Turnos', 'route' => 'turnos-comerciales.index', 'permission' => 'turnos-comerciales.ver', 'orden' => 12],
            ['parent' => 'Comercial', 'label' => 'Hojas de Ruta', 'route' => 'hojas-ruta.index', 'permission' => 'hojas-ruta.ver', 'orden' => 13],
            ['parent' => 'Comercial', 'label' => 'Devoluciones', 'route' => 'devoluciones.index', 'permission' => 'devoluciones.ver', 'orden' => 26],

            // Facturación
            ['parent' => 'Facturación', 'label' => 'Facturas', 'route' => 'facturas.index', 'permission' => 'facturas.ver', 'orden' => 1],
            ['parent' => 'Facturación', 'label' => 'Prefacturas', 'route' => 'prefacturas.index', 'permission' => 'prefacturas.ver', 'orden' => 2],
            ['parent' => 'Facturación', 'label' => 'Aforos Pendientes', 'route' => 'aforos.pendientes', 'permission' => 'facturas.ver', 'orden' => 3],

            // RRHH
            ['parent' => 'RRHH', 'label' => 'Bolsa', 'route' => 'bolsa.index', 'permission' => 'bolsa.ver', 'orden' => 1],
            ['parent' => 'RRHH', 'label' => 'Historial', 'route' => 'historial-movimientos.index', 'permission' => 'historial-movimientos.ver', 'orden' => 3],
            ['parent' => 'RRHH', 'label' => 'Salarios', 'route' => 'salarios.index', 'permission' => 'salarios.ver', 'orden' => 10],
            ['parent' => 'RRHH', 'label' => 'Vacaciones', 'route' => 'vacaciones.index', 'permission' => 'vacaciones.ver', 'orden' => 49],
            ['parent' => 'RRHH', 'label' => 'Empleados', 'route' => 'empleados.index', 'permission' => 'empleados.ver', 'orden' => 47],
            ['parent' => 'RRHH', 'label' => 'Desc. Empleados', 'route' => 'descuentos-empleados.index', 'permission' => 'descuentos-empleados.ver', 'orden' => 48],

            // Contabilidad
            ['parent' => 'Contabilidad', 'label' => 'Conciliaciones', 'route' => 'conciliaciones.index', 'permission' => 'conciliaciones.ver', 'orden' => 1],
            ['parent' => 'Contabilidad', 'label' => 'Otros Gastos', 'route' => 'otros-gastos.index', 'permission' => 'otros-gastos.ver', 'orden' => 3],
            ['parent' => 'Contabilidad', 'label' => 'Carga Combustible', 'route' => 'combustible-cargas.index', 'permission' => 'combustible-cargas.ver', 'orden' => 4],
            ['parent' => 'Contabilidad', 'label' => 'Descarga Combustible', 'route' => 'combustible-descargas.index', 'permission' => 'combustible-descargas.ver', 'orden' => 5],
            ['parent' => 'Contabilidad', 'label' => 'Inventario', 'route' => 'inventario.index', 'permission' => 'inventario.ver', 'orden' => 6],
            ['parent' => 'Contabilidad', 'label' => 'Vales', 'route' => 'vales.index', 'permission' => 'vales.ver', 'orden' => 7],
            ['parent' => 'Contabilidad', 'label' => 'Servicentros', 'route' => 'servicentros.index', 'permission' => 'servicentros.ver', 'orden' => 8],
            ['parent' => 'Contabilidad', 'label' => 'Reportes Costos', 'route' => 'reportes-costos.index', 'permission' => 'reportes-costos.ver', 'orden' => 11],
            ['parent' => 'Contabilidad', 'label' => 'Estados Tarjetas', 'route' => 'estados-tarjetas.index', 'permission' => 'estados-tarjetas.ver', 'orden' => 12],
            ['parent' => 'Contabilidad', 'label' => 'Comb. Lubricantes', 'route' => 'combustibles-lubricantes.index', 'permission' => 'combustibles-lubricantes.ver', 'orden' => 14],
            ['parent' => 'Contabilidad', 'label' => 'Pagos', 'route' => 'pagos.index', 'permission' => 'pagos.ver', 'orden' => 15],

            // Administración
            ['parent' => 'Administración', 'label' => 'Usuarios', 'route' => 'usuarios.index', 'permission' => 'usuarios.ver', 'orden' => 1],
            ['parent' => 'Administración', 'label' => 'Perfiles', 'route' => 'perfiles.index', 'permission' => 'perfiles.ver', 'orden' => 2],
            ['parent' => 'Administración', 'label' => 'Menús', 'route' => 'menu-items.index', 'permission' => 'menus.admin', 'orden' => 3],

            // Reportes
            ['parent' => 'Reportes', 'label' => 'Listado de Marcas', 'route' => 'reportes.marcas', 'permission' => 'reportes.generar', 'orden' => 1],
            ['parent' => 'Reportes', 'label' => 'Listado de Modelos', 'route' => 'reportes.modelos', 'permission' => 'reportes.generar', 'orden' => 2],
            ['parent' => 'Reportes', 'label' => 'Salario Prenómina', 'route' => 'reportes.salario-prenomina', 'permission' => 'reportes.generar', 'orden' => 3],
            ['parent' => 'Reportes', 'label' => 'Salario Choferes', 'route' => 'reportes.salario-choferes', 'permission' => 'reportes.generar', 'orden' => 4],
        ];

        foreach ($hijos as $h) {
            MenuItem::updateOrCreate(
                ['route' => $h['route']],
                [
                    'label' => $h['label'],
                    'parent_id' => $parentIds[$h['parent']],
                    'icon' => 'pi pi-circle-fill',
                    'permission' => $h['permission'],
                    'orden' => $h['orden'],
                    'activo' => true,
                ]
            );
        }

        // Tipos catálogo — todos los que tienen ruta pero no estaban en el menú
        $tiposCatalogo = [
            // === Catálogos Técnica ===
            ['parent' => 'Catálogos', 'label' => 'Tipos Combustible',        'route' => 'tipos-combustibles.index',        'permission' => 'tipos-combustibles.ver',    'orden' => 31],
            ['parent' => 'Catálogos', 'label' => 'Tipos Neumático',          'route' => 'tipos-neumaticos.index',          'permission' => 'tipos-neumaticos.ver',      'orden' => 32],
            ['parent' => 'Catálogos', 'label' => 'Tipos Equipo',             'route' => 'tipos-equipos.index',             'permission' => 'tipos-equipos.ver',         'orden' => 33],
            ['parent' => 'Catálogos', 'label' => 'Tipos Agregado',           'route' => 'tipos-agregados.index',           'permission' => 'tipos-agregados.ver',       'orden' => 34],
            ['parent' => 'Catálogos', 'label' => 'Tipos Aceite',             'route' => 'tipos-aceites.index',             'permission' => 'tipos-aceites.ver',         'orden' => 35],
            ['parent' => 'Catálogos', 'label' => 'Tipos Rotura',             'route' => 'tipos-roturas.index',             'permission' => 'tipos-roturas.ver',         'orden' => 36],
            ['parent' => 'Catálogos', 'label' => 'Tipos Sistema',            'route' => 'tipos-sistemas.index',            'permission' => 'tipos-sistemas.ver',        'orden' => 37],
            ['parent' => 'Catálogos', 'label' => 'Tipos Suspensión',         'route' => 'tipos-suspension.index',          'permission' => 'tipos-suspension.ver',      'orden' => 38],
            ['parent' => 'Catálogos', 'label' => 'Tipos Carga Reporte',      'route' => 'tipos-cargas-reporte.index',      'permission' => 'tipos-cargas-reporte.ver',  'orden' => 40],

            // === Catálogos Comercial / Contabilidad ===
            ['parent' => 'Catálogos', 'label' => 'Tipos Servicio',           'route' => 'tipos-servicios.index',           'permission' => 'tipos-servicios.ver',       'orden' => 41],
            ['parent' => 'Catálogos', 'label' => 'Tipos Estado',             'route' => 'tipos-estados.index',             'permission' => 'tipos-estados.ver',         'orden' => 42],
            ['parent' => 'Catálogos', 'label' => 'Tipos Tasa',               'route' => 'tipos-tasas.index',               'permission' => 'tipos-tasas.ver',           'orden' => 44],
            ['parent' => 'Catálogos', 'label' => 'Tipos Gasto',              'route' => 'tipos-gastos.index',              'permission' => 'tipos-gastos.ver',          'orden' => 45],
            ['parent' => 'Catálogos', 'label' => 'Tipo Ingresos',            'route' => 'tipo-ingresos.index',             'permission' => 'tipo-ingresos.ver',         'orden' => 46],
            ['parent' => 'Catálogos', 'label' => 'Tipos Concepto',           'route' => 'tipos-conceptos.index',           'permission' => 'tipos-conceptos.ver',       'orden' => 47],
            ['parent' => 'Catálogos', 'label' => 'Tipos Cat. Lugares',       'route' => 'tipos-catalogo-lugares.index',    'permission' => 'tipos-catalogo-lugares.ver', 'orden' => 49],

            // === Geográficos ===
            ['parent' => 'Catálogos', 'label' => 'Colores',                  'route' => 'colores.index',                   'permission' => 'colores.ver',               'orden' => 51],
            ['parent' => 'Catálogos', 'label' => 'Países',                   'route' => 'paises.index',                    'permission' => 'paises.ver',                'orden' => 52],
            ['parent' => 'Catálogos', 'label' => 'Provincias',               'route' => 'provincias.index',                'permission' => 'provincias.ver',            'orden' => 53],
            ['parent' => 'Catálogos', 'label' => 'Municipios',               'route' => 'municipios.index',                'permission' => 'municipios.ver',            'orden' => 54],

            // === Catálogos RRHH — datos personales ===
            ['parent' => 'Catálogos', 'label' => 'Tipos Sexo',               'route' => 'tipos-sexo.index',                'permission' => 'tipos-sexo.ver',            'orden' => 55],
            ['parent' => 'Catálogos', 'label' => 'Tipos Estado Civil',       'route' => 'tipos-estado-civil.index',        'permission' => 'tipos-estado-civil.ver',    'orden' => 56],
            ['parent' => 'Catálogos', 'label' => 'Tipos Color Piel',         'route' => 'tipos-color-piel.index',          'permission' => 'tipos-color-piel.ver',      'orden' => 57],
            ['parent' => 'Catálogos', 'label' => 'Tipos Nivel Educación',    'route' => 'tipos-nivel-educacion.index',     'permission' => 'tipos-nivel-educacion.ver', 'orden' => 58],

            // === Catálogos RRHH — laboral ===
            ['parent' => 'Catálogos', 'label' => 'Tipos Grupo Horario',      'route' => 'tipos-grupo-horario.index',       'permission' => 'tipos-grupo-horario.ver',   'orden' => 61],
            ['parent' => 'Catálogos', 'label' => 'Tipos Contrato',           'route' => 'tipos-contratos.index',           'permission' => 'tipos-contratos.ver',       'orden' => 62],
            ['parent' => 'Catálogos', 'label' => 'Tipos Deducción',          'route' => 'tipos-deducciones.index',         'permission' => 'tipos-deducciones.ver',     'orden' => 63],
            ['parent' => 'Catálogos', 'label' => 'Tipos Sistema Pago',       'route' => 'tipos-sistemas-pago.index',       'permission' => 'tipos-sistemas-pago.ver',   'orden' => 64],
            ['parent' => 'Catálogos', 'label' => 'Tipos Pago Adicional',     'route' => 'tipos-pagos-adicionales.index',   'permission' => 'tipos-pagos-adicionales.ver', 'orden' => 68],
            ['parent' => 'Catálogos', 'label' => 'Tipos Medio Cargo',        'route' => 'tipos-medios-cargo.index',        'permission' => 'tipos-medios-cargo.ver',    'orden' => 69],
            ['parent' => 'Catálogos', 'label' => 'Tipos Medio Protección',   'route' => 'tipos-medios-proteccion.index',   'permission' => 'tipos-medios-proteccion.ver', 'orden' => 70],
            ['parent' => 'Catálogos', 'label' => 'Tipos Ubicación Defensa',  'route' => 'tipos-ubicacion-defensa.index',   'permission' => 'tipos-ubicacion-defensa.ver', 'orden' => 71],
            ['parent' => 'Catálogos', 'label' => 'Tipos Integración Polít.', 'route' => 'tipos-integracion-politica.index', 'permission' => 'tipos-integracion-politica.ver', 'orden' => 72],

            // === Otros catálogos ===
            ['parent' => 'Catálogos', 'label' => 'Categorías Productos',     'route' => 'categorias-productos.index',      'permission' => 'categorias-productos.ver',  'orden' => 75],

            // === Otros módulos ===
            ['parent' => 'Flota',        'label' => 'Estad. Explotación',    'route' => 'estadisticas-explotacion.index',  'permission' => 'estadisticas-explotacion.ver', 'orden' => 33],
            ['parent' => 'Contabilidad', 'label' => 'Mov. Inventario',       'route' => 'movimientos-inventario.index',    'permission' => 'movimientos-inventario.ver', 'orden' => 9],
            ['parent' => 'Contabilidad', 'label' => 'Detalle Carga Comb.',   'route' => 'detalles-carga-combustible.index', 'permission' => 'detalles-carga-combustible.ver', 'orden' => 13],
            ['parent' => 'Taller',       'label' => 'Registro OT',           'route' => 'registro-ordenes-taller.index',   'permission' => 'registro-ordenes-taller.ver', 'orden' => 17],
        ];

        foreach ($tiposCatalogo as $h) {
            MenuItem::updateOrCreate(
                ['route' => $h['route']],
                [
                    'label' => $h['label'],
                    'parent_id' => $parentIds[$h['parent']],
                    'icon' => 'pi pi-circle-fill',
                    'permission' => $h['permission'],
                    'orden' => $h['orden'],
                    'activo' => true,
                ]
            );
        }
    }
}
