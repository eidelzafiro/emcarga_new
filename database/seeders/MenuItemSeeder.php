<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        MenuItem::updateOrCreate(
            ['route' => 'dashboard'],
            ['label' => 'Dashboard', 'icon' => 'pi pi-home', 'permission' => 'dashboard.ver', 'orden' => 1]
        );

        $flota = MenuItem::updateOrCreate(
            ['label' => 'Flota', 'parent_id' => null],
            ['icon' => 'pi pi-truck', 'route' => null, 'permission' => null, 'orden' => 3]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tractivos.index'],
            ['label' => 'Vehículos', 'icon' => 'pi pi-truck', 'permission' => 'tractivos.ver', 'orden' => 1, 'parent_id' => $flota->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'motores.index'],
            ['label' => 'Motores', 'icon' => 'pi pi-cog', 'permission' => 'motores.ver', 'orden' => 2, 'parent_id' => $flota->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'cajas.index'],
            ['label' => 'Cajas', 'icon' => 'pi pi-cog', 'permission' => 'cajas.ver', 'orden' => 3, 'parent_id' => $flota->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'diferenciales.index'],
            ['label' => 'Diferenciales', 'icon' => 'pi pi-cog', 'permission' => 'diferenciales.ver', 'orden' => 4, 'parent_id' => $flota->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'baterias.index'],
            ['label' => 'Baterías', 'icon' => 'pi pi-bolt', 'permission' => 'baterias.ver', 'orden' => 5, 'parent_id' => $flota->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'neumaticos.index'],
            ['label' => 'Neumáticos', 'icon' => 'pi pi-cog', 'permission' => 'neumaticos.ver', 'orden' => 6, 'parent_id' => $flota->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'lubricantes.index'],
            ['label' => 'Lubricantes', 'icon' => 'pi pi-eye-dropper', 'permission' => 'lubricantes.ver', 'orden' => 7, 'parent_id' => $flota->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'otros-agregados.index'],
            ['label' => 'Otros Agregados', 'icon' => 'pi pi-cog', 'permission' => 'otros-agregados.ver', 'orden' => 8, 'parent_id' => $flota->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'energia.index'],
            ['label' => 'Energía', 'icon' => 'pi pi-bolt', 'permission' => 'energia.ver', 'orden' => 9, 'parent_id' => $flota->id]
        );

        // Flota - Tablas faltantes
        MenuItem::updateOrCreate(
            ['route' => 'arrastres.index'],
            ['label' => 'Arrastres', 'icon' => 'pi pi-truck', 'permission' => 'arrastres.ver', 'orden' => 10, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'motivos-entrada-taller.index'],
            ['label' => 'Motivos Entrada Taller', 'icon' => 'pi pi-sign-in', 'permission' => 'motivos-entrada-taller.ver', 'orden' => 11, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'tipos-roturas.index'],
            ['label' => 'Tipos Roturas', 'icon' => 'pi pi-exclamation-triangle', 'permission' => 'tipos-roturas.ver', 'orden' => 12, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'clasificaciones-ordenes-taller.index'],
            ['label' => 'Clasif. OT', 'icon' => 'pi pi-tags', 'permission' => 'clasificaciones-ordenes-taller.ver', 'orden' => 13, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'tipos-sistemas.index'],
            ['label' => 'Tipos Sistemas', 'icon' => 'pi pi-cog', 'permission' => 'tipos-sistemas.ver', 'orden' => 14, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'tipos-suspension.index'],
            ['label' => 'Tipos Suspensión', 'icon' => 'pi pi-cog', 'permission' => 'tipos-suspension.ver', 'orden' => 15, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'motivos-baja-bateria.index'],
            ['label' => 'Motivos Baja Batería', 'icon' => 'pi pi-bolt', 'permission' => 'motivos-baja-bateria.ver', 'orden' => 16, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'locales-electricos.index'],
            ['label' => 'Locales Eléctricos', 'icon' => 'pi pi-building', 'permission' => 'locales-electricos.ver', 'orden' => 17, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'balances-electricos.index'],
            ['label' => 'Balances Eléctricos', 'icon' => 'pi pi-chart-bar', 'permission' => 'balances-electricos.ver', 'orden' => 18, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'historial-tractivos.index'],
            ['label' => 'Historial Tractivos', 'icon' => 'pi pi-history', 'permission' => 'historial-tractivos.ver', 'orden' => 21, 'parent_id' => $flota->id]
        );
        
        MenuItem::updateOrCreate(
            ['route' => 'tipos-aceites.index'],
            ['label' => 'Tipos Aceites', 'icon' => 'pi pi-eye-dropper', 'permission' => 'tipos-aceites.ver', 'orden' => 31, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'choferes.index'],
            ['label' => 'Choferes', 'icon' => 'pi pi-user', 'permission' => 'choferes.ver', 'orden' => 32, 'parent_id' => $flota->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'estadisticas-explotacion.index'],
            ['label' => 'Estad. Explotación', 'icon' => 'pi pi-chart-line', 'permission' => 'estadisticas-explotacion.ver', 'orden' => 33, 'parent_id' => $flota->id]
        );
        
        MenuItem::updateOrCreate(
            ['route' => 'taller.index'],
            ['label' => 'Taller', 'icon' => 'pi pi-wrench', 'permission' => 'taller.ver', 'orden' => 4]
        );

        $comercial = MenuItem::updateOrCreate(
            ['label' => 'Comercial', 'parent_id' => null],
            ['icon' => 'pi pi-briefcase', 'route' => null, 'permission' => null, 'orden' => 5]
        );

        MenuItem::updateOrCreate(
            ['route' => 'clientes.index'],
            ['label' => 'Clientes', 'icon' => 'pi pi-users', 'permission' => 'clientes.ver', 'orden' => 1, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'lugares.index'],
            ['label' => 'Lugares', 'icon' => 'pi pi-map-marker', 'permission' => 'lugares.ver', 'orden' => 2, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'distancias.index'],
            ['label' => 'Distancias', 'icon' => 'pi pi-arrows-alt', 'permission' => 'distancias.ver', 'orden' => 3, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'acuerdos.index'],
            ['label' => 'Acuerdos', 'icon' => 'pi pi-file', 'permission' => 'acuerdos.ver', 'orden' => 4, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'solicitudes.index'],
            ['label' => 'Solicitudes', 'icon' => 'pi pi-envelope', 'permission' => 'solicitudes.ver', 'orden' => 5, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'giros.index'],
            ['label' => 'Cartas Porte', 'icon' => 'pi pi-file', 'permission' => 'giros.ver', 'orden' => 6, 'parent_id' => $comercial->id]
        );

        // Comercial - Tablas faltantes
        MenuItem::updateOrCreate(
            ['route' => 'alertas.index'],
            ['label' => 'Alertas', 'icon' => 'pi pi-bell', 'permission' => 'alertas.ver', 'orden' => 7, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tarifas.index'],
            ['label' => 'Tarifas', 'icon' => 'pi pi-dollar', 'permission' => 'tarifas.ver', 'orden' => 8, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'demandas.index'],
            ['label' => 'Demandas', 'icon' => 'pi pi-chart-bar', 'permission' => 'demandas.ver', 'orden' => 9, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'indicadores.index'],
            ['label' => 'Indicadores', 'icon' => 'pi pi-chart-line', 'permission' => 'indicadores.ver', 'orden' => 10, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'pizarra-tractivos.index'],
            ['label' => 'Pizarra Tractivos', 'icon' => 'pi pi-th-large', 'permission' => 'pizarra-tractivos.ver', 'orden' => 11, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'turnos-comerciales.index'],
            ['label' => 'Turnos', 'icon' => 'pi pi-clock', 'permission' => 'turnos-comerciales.ver', 'orden' => 12, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'clientes-seleccion.index'],
            ['label' => 'Clientes Selección', 'icon' => 'pi pi-verified', 'permission' => 'clientes-seleccion.ver', 'orden' => 13, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'otros-ingresos-pre.index'],
            ['label' => 'Otros Ingresos Pre', 'icon' => 'pi pi-plus-circle', 'permission' => 'otros-ingresos-pre.ver', 'orden' => 14, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-estados.index'],
            ['label' => 'Tipos Estados', 'icon' => 'pi pi-flag', 'permission' => 'tipos-estados.ver', 'orden' => 15, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-catalogo-lugares.index'],
            ['label' => 'Catálogo Lugares', 'icon' => 'pi pi-bookmark', 'permission' => 'tipos-catalogo-lugares.ver', 'orden' => 16, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-modelo.index'],
            ['label' => 'Tipos Modelo', 'icon' => 'pi pi-cog', 'permission' => 'tipos-modelo.ver', 'orden' => 17, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'configuraciones-modelo.index'],
            ['label' => 'Config. Modelo', 'icon' => 'pi pi-sliders-h', 'permission' => 'configuraciones-modelo.ver', 'orden' => 18, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-cargas-reporte.index'],
            ['label' => 'Cargas Reporte', 'icon' => 'pi pi-truck', 'permission' => 'tipos-cargas-reporte.ver', 'orden' => 19, 'parent_id' => $comercial->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'movil-web.index'],
            ['label' => 'Móvil Web', 'icon' => 'pi pi-mobile', 'permission' => 'movil-web.ver', 'orden' => 20, 'parent_id' => $comercial->id]
        );
        // Comercial - Tablas faltantes parte 2
        MenuItem::updateOrCreate(
            ['route' => 'contenedores.index'],
            ['label' => 'Contenedores', 'icon' => 'pi pi-box', 'permission' => 'contenedores.ver', 'orden' => 22, 'parent_id' => $comercial->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'categorias-productos.index'],
            ['label' => 'Categorías Productos', 'icon' => 'pi pi-tags', 'permission' => 'categorias-productos.ver', 'orden' => 23, 'parent_id' => $comercial->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'devoluciones.index'],
            ['label' => 'Devoluciones', 'icon' => 'pi pi-undo', 'permission' => 'devoluciones.ver', 'orden' => 26, 'parent_id' => $comercial->id]
        );

        $facturacion = MenuItem::updateOrCreate(
            ['label' => 'Facturación', 'parent_id' => null],
            ['icon' => 'pi pi-file', 'route' => null, 'permission' => null, 'orden' => 6]
        );

        MenuItem::updateOrCreate(
            ['route' => 'facturas.index'],
            ['label' => 'Facturas', 'icon' => 'pi pi-file', 'permission' => 'facturas.ver', 'orden' => 1, 'parent_id' => $facturacion->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'prefacturas.index'],
            ['label' => 'Prefacturas', 'icon' => 'pi pi-file-edit', 'permission' => 'prefacturas.ver', 'orden' => 2, 'parent_id' => $facturacion->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipo-ingresos.index'],
            ['label' => 'Tipos de Ingreso', 'icon' => 'pi pi-tag', 'permission' => 'tipo-ingresos.ver', 'orden' => 3, 'parent_id' => $facturacion->id]
        );

        $rrhh = MenuItem::updateOrCreate(
            ['label' => 'RRHH', 'parent_id' => null],
            ['icon' => 'pi pi-users', 'route' => null, 'permission' => null, 'orden' => 7]
        );

        MenuItem::updateOrCreate(
            ['route' => 'bolsa.index'],
            ['label' => 'Bolsa', 'icon' => 'pi pi-user', 'permission' => 'bolsa.ver', 'orden' => 1, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'plantilla.index'],
            ['label' => 'Plantilla', 'icon' => 'pi pi-table', 'permission' => 'plantilla.ver', 'orden' => 2, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'historial-movimientos.index'],
            ['label' => 'Historial', 'icon' => 'pi pi-history', 'permission' => 'historial-movimientos.ver', 'orden' => 3, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-incidencias.index'],
            ['label' => 'Tipos Incidencias', 'icon' => 'pi pi-exclamation-triangle', 'permission' => 'tipos-incidencias.ver', 'orden' => 4, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-penalizaciones.index'],
            ['label' => 'Tipos Penalizaciones', 'icon' => 'pi pi-ban', 'permission' => 'tipos-penalizaciones.ver', 'orden' => 5, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-contratos.index'],
            ['label' => 'Tipos Contratos', 'icon' => 'pi pi-file', 'permission' => 'tipos-contratos.ver', 'orden' => 6, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-sistemas-pago.index'],
            ['label' => 'Sistemas de Pago', 'icon' => 'pi pi-dollar', 'permission' => 'tipos-sistemas-pago.ver', 'orden' => 7, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-pagos-adicionales.index'],
            ['label' => 'Pagos Adicionales', 'icon' => 'pi pi-plus-circle', 'permission' => 'tipos-pagos-adicionales.ver', 'orden' => 8, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-tasas.index'],
            ['label' => 'Tipos Tasas', 'icon' => 'pi pi-percentage', 'permission' => 'tipos-tasas.ver', 'orden' => 9, 'parent_id' => $rrhh->id]
        );

        // RRHH - Tablas faltantes (Fase 5.5 parte 3)
        MenuItem::updateOrCreate(
            ['route' => 'salarios.index'],
            ['label' => 'Salarios', 'icon' => 'pi pi-dollar', 'permission' => 'salarios.ver', 'orden' => 10, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'salarios-administrativos.index'],
            ['label' => 'Salarios Admin.', 'icon' => 'pi pi-briefcase', 'permission' => 'salarios-administrativos.ver', 'orden' => 11, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'provincias.index'],
            ['label' => 'Provincias', 'icon' => 'pi pi-map', 'permission' => 'provincias.ver', 'orden' => 12, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'municipios.index'],
            ['label' => 'Municipios', 'icon' => 'pi pi-map-marker', 'permission' => 'municipios.ver', 'orden' => 13, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'osdes.index'],
            ['label' => 'OSDEs', 'icon' => 'pi pi-building', 'permission' => 'osdes.ver', 'orden' => 14, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'firmas.index'],
            ['label' => 'Firmas', 'icon' => 'pi pi-file-edit', 'permission' => 'firmas.ver', 'orden' => 15, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'meses.index'],
            ['label' => 'Meses', 'icon' => 'pi pi-calendar', 'permission' => 'meses.ver', 'orden' => 16, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'fondos-tiempo.index'],
            ['label' => 'Fondos Tiempo', 'icon' => 'pi pi-clock', 'permission' => 'fondos-tiempo.ver', 'orden' => 17, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'medios-proteccion.index'],
            ['label' => 'Medios Protección', 'icon' => 'pi pi-shield', 'permission' => 'medios-proteccion.ver', 'orden' => 18, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-medios-cargo.index'],
            ['label' => 'Medios x Cargo', 'icon' => 'pi pi-link', 'permission' => 'tipos-medios-cargo.ver', 'orden' => 19, 'parent_id' => $rrhh->id]
        );

        // RRHH - Catálogos pequeños
        MenuItem::updateOrCreate(
            ['route' => 'tipos-calificadores.index'],
            ['label' => 'Tipos Calificadores', 'icon' => 'pi pi-star', 'permission' => 'tipos-calificadores.ver', 'orden' => 20, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-causas-laborales.index'],
            ['label' => 'Causas Laborales', 'icon' => 'pi pi-question-circle', 'permission' => 'tipos-causas-laborales.ver', 'orden' => 21, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-causas-baja.index'],
            ['label' => 'Causas Baja', 'icon' => 'pi pi-times-circle', 'permission' => 'tipos-causas-baja.ver', 'orden' => 22, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-causas-movimiento.index'],
            ['label' => 'Causas Movimiento', 'icon' => 'pi pi-arrows-alt', 'permission' => 'tipos-causas-movimiento.ver', 'orden' => 23, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-clasificacion-laboral.index'],
            ['label' => 'Clasif. Laboral', 'icon' => 'pi pi-tags', 'permission' => 'tipos-clasificacion-laboral.ver', 'orden' => 24, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-color-piel.index'],
            ['label' => 'Color Piel', 'icon' => 'pi pi-palette', 'permission' => 'tipos-color-piel.ver', 'orden' => 25, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-deducciones.index'],
            ['label' => 'Deducciones', 'icon' => 'pi pi-minus-circle', 'permission' => 'tipos-deducciones.ver', 'orden' => 26, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-especialidad.index'],
            ['label' => 'Especialidad', 'icon' => 'pi pi-graduation-cap', 'permission' => 'tipos-especialidad.ver', 'orden' => 27, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-estado-civil.index'],
            ['label' => 'Estado Civil', 'icon' => 'pi pi-users', 'permission' => 'tipos-estado-civil.ver', 'orden' => 28, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-grupo-horario.index'],
            ['label' => 'Grupo Horario', 'icon' => 'pi pi-clock', 'permission' => 'tipos-grupo-horario.ver', 'orden' => 29, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-integracion-politica.index'],
            ['label' => 'Integr. Política', 'icon' => 'pi pi-flag', 'permission' => 'tipos-integracion-politica.ver', 'orden' => 30, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-medios-proteccion.index'],
            ['label' => 'Medios Protección', 'icon' => 'pi pi-shield', 'permission' => 'tipos-medios-proteccion.ver', 'orden' => 31, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-nivel-educacion.index'],
            ['label' => 'Nivel Educación', 'icon' => 'pi pi-book', 'permission' => 'tipos-nivel-educacion.ver', 'orden' => 32, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-plantillas.index'],
            ['label' => 'Tipos Plantilla', 'icon' => 'pi pi-file', 'permission' => 'tipos-plantillas.ver', 'orden' => 33, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-sexo.index'],
            ['label' => 'Tipos Sexo', 'icon' => 'pi pi-venus', 'permission' => 'tipos-sexo.ver', 'orden' => 34, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-tallas.index'],
            ['label' => 'Tipos Tallas', 'icon' => 'pi pi-arrows-h', 'permission' => 'tipos-tallas.ver', 'orden' => 35, 'parent_id' => $rrhh->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-ubicacion-defensa.index'],
            ['label' => 'Ubic. Defensa', 'icon' => 'pi pi-map-marker', 'permission' => 'tipos-ubicacion-defensa.ver', 'orden' => 36, 'parent_id' => $rrhh->id]
        );

        // RRHH - Tablas faltantes parte 2
        MenuItem::updateOrCreate(
            ['route' => 'centros-costos.index'],
            ['label' => 'Centros Costo', 'icon' => 'pi pi-dollar', 'permission' => 'centros-costos.ver', 'orden' => 38, 'parent_id' => $rrhh->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'tipos-articulos-bolsa.index'],
            ['label' => 'Artículos Bolsa', 'icon' => 'pi pi-shopping-bag', 'permission' => 'tipos-articulos-bolsa.ver', 'orden' => 39, 'parent_id' => $rrhh->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'tipos-jefe-grupo.index'],
            ['label' => 'Jefes Grupo', 'icon' => 'pi pi-id-card', 'permission' => 'tipos-jefe-grupo.ver', 'orden' => 42, 'parent_id' => $rrhh->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'pagos-adicionales-cargo.index'],
            ['label' => 'Pagos Adic. Cargo', 'icon' => 'pi pi-money-bill', 'permission' => 'pagos-adicionales-cargo.ver', 'orden' => 43, 'parent_id' => $rrhh->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'tipos-entidad.index'],
            ['label' => 'Tipos Entidad', 'icon' => 'pi pi-building', 'permission' => 'tipos-entidad.ver', 'orden' => 46, 'parent_id' => $rrhh->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'empleados.index'],
            ['label' => 'Empleados', 'icon' => 'pi pi-users', 'permission' => 'empleados.ver', 'orden' => 47, 'parent_id' => $rrhh->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'descuentos-empleados.index'],
            ['label' => 'Desc. Empleados', 'icon' => 'pi pi-minus-circle', 'permission' => 'descuentos-empleados.ver', 'orden' => 48, 'parent_id' => $rrhh->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'vacaciones.index'],
            ['label' => 'Vacaciones', 'icon' => 'pi pi-calendar', 'permission' => 'vacaciones.ver', 'orden' => 49, 'parent_id' => $rrhh->id]
        );

        $contabilidad = MenuItem::updateOrCreate(
            ['label' => 'Contabilidad', 'parent_id' => null],
            ['icon' => 'pi pi-book', 'route' => null, 'permission' => null, 'orden' => 8]
        );

        MenuItem::updateOrCreate(
            ['route' => 'conciliaciones.index'],
            ['label' => 'Conciliaciones', 'icon' => 'pi pi-check-circle', 'permission' => 'conciliaciones.ver', 'orden' => 1, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-conceptos.index'],
            ['label' => 'Tipos Conceptos', 'icon' => 'pi pi-tag', 'permission' => 'tipos-conceptos.ver', 'orden' => 2, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'otros-gastos.index'],
            ['label' => 'Otros Gastos', 'icon' => 'pi pi-dollar', 'permission' => 'otros-gastos.ver', 'orden' => 3, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'combustible-cargas.index'],
            ['label' => 'Carga Combustible', 'icon' => 'pi pi-gauge', 'permission' => 'combustible-cargas.ver', 'orden' => 4, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'combustible-descargas.index'],
            ['label' => 'Descarga Combustible', 'icon' => 'pi pi-download', 'permission' => 'combustible-descargas.ver', 'orden' => 5, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'inventario.index'],
            ['label' => 'Inventario', 'icon' => 'pi pi-box', 'permission' => 'inventario.ver', 'orden' => 6, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'vales.index'],
            ['label' => 'Vales', 'icon' => 'pi pi-ticket', 'permission' => 'vales.ver', 'orden' => 7, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'servicentros.index'],
            ['label' => 'Servicentros', 'icon' => 'pi pi-map', 'permission' => 'servicentros.ver', 'orden' => 8, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'tipos-documentos.index'],
            ['label' => 'Tipos Documentos', 'icon' => 'pi pi-file', 'permission' => 'tipos-documentos.ver', 'orden' => 9, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'firmas-autorizadas.index'],
            ['label' => 'Firmas Autorizadas', 'icon' => 'pi pi-pencil', 'permission' => 'firmas-autorizadas.ver', 'orden' => 10, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'reportes-costos.index'],
            ['label' => 'Reportes Costos', 'icon' => 'pi pi-chart-line', 'permission' => 'reportes-costos.ver', 'orden' => 11, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'estados-tarjetas.index'],
            ['label' => 'Estados Tarjetas', 'icon' => 'pi pi-credit-card', 'permission' => 'estados-tarjetas.ver', 'orden' => 12, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'combustibles-lubricantes.index'],
            ['label' => 'Comb. Lubricantes', 'icon' => 'pi pi-eye-dropper', 'permission' => 'combustibles-lubricantes.ver', 'orden' => 14, 'parent_id' => $contabilidad->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'pagos.index'],
            ['label' => 'Pagos', 'icon' => 'pi pi-money-bill', 'permission' => 'pagos.ver', 'orden' => 15, 'parent_id' => $contabilidad->id]
        );
        MenuItem::updateOrCreate(
            ['route' => 'elementos-gasto.index'],
            ['label' => 'Elementos Gasto', 'icon' => 'pi pi-receipt', 'permission' => 'elementos-gasto.ver', 'orden' => 16, 'parent_id' => $contabilidad->id]
        );

        $catalogos = MenuItem::updateOrCreate(
            ['label' => 'Catálogos', 'parent_id' => null],
            ['icon' => 'pi pi-bookmark', 'route' => null, 'permission' => null, 'orden' => 9]
        );

        $catItems = [
            ['marcas.index', 'Marcas', 'pi pi-tag', 'marcas.ver', 1],
            ['modelos.index', 'Modelos', 'pi pi-cog', 'modelos.ver', 2],
            ['paises.index', 'Países', 'pi pi-globe', 'paises.ver', 3],
            ['grupos.index', 'Grupos', 'pi pi-bullseye', 'grupos.ver', 4],
            ['colores.index', 'Colores', 'pi pi-palette', 'colores.ver', 5],
            ['talleres.index', 'Talleres', 'pi pi-wrench', 'talleres.ver', 6],
            ['naves.index', 'Naves', 'pi pi-building', 'naves.ver', 7],
            ['vallas.index', 'Vallas', 'pi pi-th-large', 'vallas.ver', 8],
            ['destinos-agregados.index', 'Destinos Agregados', 'pi pi-map', 'destinos-agregados.ver', 9],
            ['medidas-neumaticos.index', 'Medidas Neumáticos', 'pi pi-arrows-h', 'medidas-neumaticos.ver', 10],
            ['tipos-combustibles.index', 'Tipos Combustible', 'pi pi-gauge', 'tipos-combustibles.ver', 11],
            ['tipos-equipos.index', 'Tipos Equipo', 'pi pi-box', 'tipos-equipos.ver', 12],
            ['tipos-agregados.index', 'Tipos Agregados', 'pi pi-clone', 'tipos-agregados.ver', 13],
            ['tipos-neumaticos.index', 'Tipos Neumáticos', 'pi pi-circle', 'tipos-neumaticos.ver', 14],
            ['posiciones-neumaticos.index', 'Posiciones Neumáticos', 'pi pi-arrows-alt', 'posiciones-neumaticos.ver', 15],
            ['consecutivos.index', 'Consecutivos', 'pi pi-list', 'consecutivos.ver', 16],
            ['tipos-servicios.index', 'Tipos Servicio', 'pi pi-briefcase', 'tipos-servicios.ver', 17],
            ['tipos-gastos.index', 'Tipos Gasto', 'pi pi-dollar', 'tipos-gastos.ver', 18],
            ['embalajes.index', 'Embalajes', 'pi pi-box', 'embalajes.ver', 19],
            ['navieras.index', 'Navieras', 'pi pi-globe', 'navieras.ver', 21],
            ['organismos.index', 'Organismos', 'pi pi-building', 'organismos.ver', 22],
            ['categorias-cargo.index', 'Categorías Cargo', 'pi pi-tags', 'categorias-cargo.ver', 23],
            ['grupos-escala.index', 'Grupos Escala', 'pi pi-chart-bar', 'grupos-escala.ver', 24],
            ['entidades.index', 'Entidades', 'pi pi-building', 'entidades.ver', 25],
        ];

        foreach ($catItems as [$route, $label, $icon, $perm, $orden]) {
            MenuItem::updateOrCreate(
                ['route' => $route],
                ['label' => $label, 'icon' => $icon, 'permission' => $perm, 'orden' => $orden, 'parent_id' => $catalogos->id]
            );
        }

        $reportes = MenuItem::updateOrCreate(
            ['label' => 'Reportes', 'parent_id' => null],
            ['icon' => 'pi pi-file-pdf', 'route' => null, 'permission' => 'reportes.ver', 'orden' => 10]
        );

        MenuItem::updateOrCreate(
            ['route' => 'reportes.marcas'],
            ['label' => 'Listado de Marcas', 'icon' => 'pi pi-tag', 'permission' => 'reportes.generar', 'orden' => 1, 'parent_id' => $reportes->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'reportes.modelos'],
            ['label' => 'Listado de Modelos', 'icon' => 'pi pi-cog', 'permission' => 'reportes.generar', 'orden' => 2, 'parent_id' => $reportes->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'reportes.paises'],
            ['label' => 'Listado de Países', 'icon' => 'pi pi-globe', 'permission' => 'reportes.generar', 'orden' => 3, 'parent_id' => $reportes->id]
        );

        $administracion = MenuItem::updateOrCreate(
            ['label' => 'Administración', 'parent_id' => null],
            ['icon' => 'pi pi-cog', 'route' => null, 'permission' => null, 'orden' => 90]
        );

        MenuItem::updateOrCreate(
            ['route' => 'usuarios.index'],
            ['label' => 'Usuarios', 'icon' => 'pi pi-users', 'permission' => 'usuarios.ver', 'orden' => 1, 'parent_id' => $administracion->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'perfiles.index'],
            ['label' => 'Perfiles', 'icon' => 'pi pi-shield', 'permission' => 'perfiles.ver', 'orden' => 2, 'parent_id' => $administracion->id]
        );

        MenuItem::updateOrCreate(
            ['route' => 'menu-items.index'],
            ['label' => 'Menús', 'icon' => 'pi pi-bars', 'permission' => 'menus.ver', 'orden' => 3, 'parent_id' => $administracion->id]
        );
    }
}
