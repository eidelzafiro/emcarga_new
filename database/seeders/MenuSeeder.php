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
            ['parent' => 'Flota', 'label' => 'Motivos Entrada Taller', 'route' => 'catalogo.index?tipo=motivos_entrada_taller', 'permission' => 'catalogo.ver', 'orden' => 11],
            ['parent' => 'Flota', 'label' => 'Clasif. OT', 'route' => 'catalogo.index?tipo=clasificaciones_ordenes_taller', 'permission' => 'catalogo.ver', 'orden' => 13],
            ['parent' => 'Flota', 'label' => 'Motivos Baja Batería', 'route' => 'catalogo.index?tipo=motivos_baja_bateria', 'permission' => 'catalogo.ver', 'orden' => 16],
            ['parent' => 'Flota', 'label' => 'Historial Tractivos', 'route' => 'historial-tractivos.index', 'permission' => 'historial-tractivos.ver', 'orden' => 21],
            ['parent' => 'Flota', 'label' => 'Estad. Explotación', 'route' => 'estadisticas-explotacion.index', 'permission' => 'estadisticas-explotacion.ver', 'orden' => 33],
            // Flota - extras
            ['parent' => 'Flota', 'label' => 'Choferes', 'route' => 'choferes.index', 'permission' => 'choferes.ver', 'orden' => 32],

            // Catálogos
            ['parent' => 'Catálogos', 'label' => 'Marcas', 'route' => 'catalogo.index?tipo=marcas', 'permission' => 'catalogo.ver', 'orden' => 1],
            ['parent' => 'Catálogos', 'label' => 'Modelos', 'route' => 'catalogo.index?tipo=modelos', 'permission' => 'catalogo.ver', 'orden' => 2],
            ['parent' => 'Catálogos', 'label' => 'Grupos', 'route' => 'catalogo.index?tipo=grupos', 'permission' => 'catalogo.ver', 'orden' => 3],
            ['parent' => 'Catálogos', 'label' => 'Talleres', 'route' => 'talleres.index', 'permission' => 'talleres.ver', 'orden' => 6],
            ['parent' => 'Catálogos', 'label' => 'Naves', 'route' => 'naves.index', 'permission' => 'naves.ver', 'orden' => 7],
            ['parent' => 'Catálogos', 'label' => 'Vallas', 'route' => 'vallas.index', 'permission' => 'vallas.ver', 'orden' => 8],
            ['parent' => 'Catálogos', 'label' => 'Destinos Agregados', 'route' => 'catalogo.index?tipo=destinos_agregados', 'permission' => 'catalogo.ver', 'orden' => 9],
            ['parent' => 'Catálogos', 'label' => 'Medidas Neumáticos', 'route' => 'catalogo.index?tipo=medidas_neumaticos', 'permission' => 'catalogo.ver', 'orden' => 10],
            ['parent' => 'Catálogos', 'label' => 'Posiciones Neumáticos', 'route' => 'catalogo.index?tipo=posiciones_neumaticos', 'permission' => 'catalogo.ver', 'orden' => 15],
            ['parent' => 'Catálogos', 'label' => 'Embalajes', 'route' => 'catalogo.index?tipo=embalajes', 'permission' => 'catalogo.ver', 'orden' => 19],
            ['parent' => 'Catálogos', 'label' => 'Organismos', 'route' => 'catalogo.index?tipo=organismos', 'permission' => 'catalogo.ver', 'orden' => 22],
            ['parent' => 'Catálogos', 'label' => 'Categorías Cargo', 'route' => 'catalogo.index?tipo=categorias_cargo', 'permission' => 'catalogo.ver', 'orden' => 23],
            ['parent' => 'Catálogos', 'label' => 'Grupos Escala', 'route' => 'grupos-escala.index', 'permission' => 'grupos-escala.ver', 'orden' => 24],
            ['parent' => 'Catálogos', 'label' => 'Cargos', 'route' => 'cargos.index', 'permission' => 'cargos.ver', 'orden' => 25],
            ['parent' => 'Catálogos', 'label' => 'Entidades', 'route' => 'entidades.index', 'permission' => 'entidades.ver', 'orden' => 26],
            ['parent' => 'Catálogos', 'label' => 'Tipos de Modelo', 'route' => 'catalogo.index?tipo=tipos_modelo', 'permission' => 'catalogo.ver', 'orden' => 27],

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
            ['parent' => 'Comercial', 'label' => 'Hojas de Ruta', 'route' => 'hojas-ruta.index', 'permission' => 'hojas-ruta.ver', 'orden' => 13],
            ['parent' => 'Comercial', 'label' => 'Devoluciones', 'route' => 'devoluciones.index', 'permission' => 'devoluciones.ver', 'orden' => 26],

            // Facturación
            ['parent' => 'Facturación', 'label' => 'Facturas', 'route' => 'facturas.index', 'permission' => 'facturas.ver', 'orden' => 1],
            ['parent' => 'Facturación', 'label' => 'Prefacturas', 'route' => 'prefacturas.index', 'permission' => 'prefacturas.ver', 'orden' => 2],
            ['parent' => 'Facturación', 'label' => 'Aforos Pendientes', 'route' => 'aforos.pendientes', 'permission' => 'facturas.ver', 'orden' => 3],

            // RRHH
            ['parent' => 'RRHH', 'label' => 'Bolsa', 'route' => 'bolsa.index', 'permission' => 'bolsa.ver', 'orden' => 1],
            ['parent' => 'RRHH', 'label' => 'Historial', 'route' => 'historial-movimientos.index', 'permission' => 'historial-movimientos.ver', 'orden' => 3],
            ['parent' => 'RRHH', 'label' => 'Empleados', 'route' => 'empleados.index', 'permission' => 'empleados.ver', 'orden' => 5],
            ['parent' => 'RRHH', 'label' => 'Plantilla', 'route' => 'plantilla.index', 'permission' => 'plantilla.ver', 'orden' => 6],
            // --- Salarios ---
            ['parent' => 'RRHH', 'label' => 'Salario Choferes', 'route' => 'salarios-choferes.index', 'permission' => 'salarios.ver', 'orden' => 10],
            ['parent' => 'RRHH', 'label' => 'Salario Administrativo', 'route' => 'salarios-administrativos.index', 'permission' => 'salarios-administrativos.ver', 'orden' => 11],
            ['parent' => 'RRHH', 'label' => 'Incidencias', 'route' => 'incidencias.index', 'permission' => 'incidencias.ver', 'orden' => 12],
            ['parent' => 'RRHH', 'label' => 'Penalizaciones', 'route' => 'penalizaciones.index', 'permission' => 'penalizaciones.ver', 'orden' => 13],
            ['parent' => 'RRHH', 'label' => 'Tasas Salariales', 'route' => 'catalogo.index?tipo=tipos_tasas', 'permission' => 'tipos-tasas.ver', 'orden' => 14],

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
            ['parent' => 'Catálogos', 'label' => 'Tipos Combustible',        'route' => 'catalogo.index?tipo=tipos_combustibles',        'permission' => 'catalogo.ver',    'orden' => 31],
            ['parent' => 'Catálogos', 'label' => 'Tipos Neumático',          'route' => 'catalogo.index?tipo=tipos_neumaticos',          'permission' => 'catalogo.ver',      'orden' => 32],
            ['parent' => 'Catálogos', 'label' => 'Tipos Equipo',             'route' => 'tipos-equipos.index',                         'permission' => 'tipos-equipos.ver',     'orden' => 33],
            ['parent' => 'Catálogos', 'label' => 'Tipos Tractivos',          'route' => 'tipos-tractivos.index',                       'permission' => 'tipos-tractivos.ver',  'orden' => 331],
            ['parent' => 'Catálogos', 'label' => 'Tipos Arrastres',          'route' => 'tipos-arrastres.index',                       'permission' => 'tipos-arrastres.ver',  'orden' => 332],
            ['parent' => 'Catálogos', 'label' => 'Tipos Agregado',           'route' => 'catalogo.index?tipo=tipos_agregados',           'permission' => 'catalogo.ver',       'orden' => 34],
            ['parent' => 'Catálogos', 'label' => 'Tipos Aceite',             'route' => 'catalogo.index?tipo=tipos_aceites',             'permission' => 'catalogo.ver',         'orden' => 35],
            ['parent' => 'Catálogos', 'label' => 'Tipos Rotura',             'route' => 'catalogo.index?tipo=tipos_roturas',             'permission' => 'catalogo.ver',         'orden' => 36],
            ['parent' => 'Catálogos', 'label' => 'Tipos Sistema',            'route' => 'catalogo.index?tipo=tipos_sistemas',            'permission' => 'catalogo.ver',        'orden' => 37],
            ['parent' => 'Catálogos', 'label' => 'Tipos Suspensión',         'route' => 'catalogo.index?tipo=tipos_suspension',          'permission' => 'catalogo.ver',      'orden' => 38],
            ['parent' => 'Catálogos', 'label' => 'Tipos Carga Reporte',      'route' => 'tipos-cargas-reporte.index',      'permission' => 'tipos-cargas-reporte.ver',  'orden' => 40],

            // === Catálogos Comercial / Contabilidad ===
            ['parent' => 'Catálogos', 'label' => 'Tipos Servicio',           'route' => 'catalogo.index?tipo=tipos_servicios',           'permission' => 'catalogo.ver',       'orden' => 41],
            ['parent' => 'Catálogos', 'label' => 'Tipos Estado',             'route' => 'catalogo.index?tipo=tipos_estados',             'permission' => 'catalogo.ver',         'orden' => 42],
            ['parent' => 'Catálogos', 'label' => 'Tipos Gasto',              'route' => 'catalogo.index?tipo=tipos_gastos',              'permission' => 'catalogo.ver',          'orden' => 45],
            ['parent' => 'Catálogos', 'label' => 'Tipo Ingresos',            'route' => 'catalogo.index?tipo=tipo_ingresos',             'permission' => 'catalogo.ver',         'orden' => 46],
            ['parent' => 'Catálogos', 'label' => 'Tipos Concepto',           'route' => 'catalogo.index?tipo=tipos_conceptos',           'permission' => 'tipos-conceptos.ver',       'orden' => 47],

            // === Geográficos ===
            ['parent' => 'Catálogos', 'label' => 'Colores',                  'route' => 'catalogo.index?tipo=colores',                   'permission' => 'catalogo.ver',               'orden' => 51],
            ['parent' => 'Catálogos', 'label' => 'Países',                   'route' => 'catalogo.index?tipo=paises',                    'permission' => 'catalogo.ver',                'orden' => 52],
            ['parent' => 'Catálogos', 'label' => 'Provincias',               'route' => 'provincias.index',                'permission' => 'provincias.ver',            'orden' => 53],
            ['parent' => 'Catálogos', 'label' => 'Municipios',               'route' => 'municipios.index',                'permission' => 'municipios.ver',            'orden' => 54],

            // === Catálogos RRHH — datos personales ===
            ['parent' => 'Catálogos', 'label' => 'Tipos Sexo',               'route' => 'catalogo.index?tipo=tipos_sexo',                'permission' => 'catalogo.ver',            'orden' => 55],
            ['parent' => 'Catálogos', 'label' => 'Tipos Estado Civil',       'route' => 'catalogo.index?tipo=tipos_estado_civil',        'permission' => 'catalogo.ver',    'orden' => 56],
            ['parent' => 'Catálogos', 'label' => 'Tipos Color Piel',         'route' => 'catalogo.index?tipo=tipos_color_piel',          'permission' => 'catalogo.ver',      'orden' => 57],
            ['parent' => 'Catálogos', 'label' => 'Tipos Nivel Educación',    'route' => 'catalogo.index?tipo=tipos_nivel_educacion',     'permission' => 'catalogo.ver', 'orden' => 58],

            // === Catálogos RRHH — laboral ===
            ['parent' => 'Catálogos', 'label' => 'Tipos Grupo Horario',      'route' => 'catalogo.index?tipo=tipos_grupo_horario',       'permission' => 'catalogo.ver',   'orden' => 61],
            ['parent' => 'Catálogos', 'label' => 'Tipos Contrato',           'route' => 'catalogo.index?tipo=tipos_contratos',           'permission' => 'tipos-contratos.ver',       'orden' => 62],
            ['parent' => 'Catálogos', 'label' => 'Tipos Deducción',          'route' => 'catalogo.index?tipo=tipos_deducciones',         'permission' => 'catalogo.ver',     'orden' => 63],
            ['parent' => 'Catálogos', 'label' => 'Tipos Sistema Pago',       'route' => 'catalogo.index?tipo=tipos_sistemas_pago',       'permission' => 'catalogo.ver',   'orden' => 64],
            ['parent' => 'Catálogos', 'label' => 'Tipos Ubicación Defensa',  'route' => 'catalogo.index?tipo=tipos_ubicacion_defensa',   'permission' => 'catalogo.ver', 'orden' => 71],
            ['parent' => 'Catálogos', 'label' => 'Tipos Integración Polít.', 'route' => 'catalogo.index?tipo=tipos_integracion_politica', 'permission' => 'catalogo.ver', 'orden' => 72],

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
