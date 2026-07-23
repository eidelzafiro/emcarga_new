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
            ['label' => 'Dashboard', 'icon' => 'home', 'permission' => 'dashboard.ver', 'orden' => 1]
        );

        MenuItem::firstOrCreate(
            ['route' => 'pizarra.index'],
            ['label' => 'Pizarra', 'icon' => 'map', 'permission' => 'pizarra.ver', 'orden' => 2]
        );

        $flota = MenuItem::firstOrCreate(
            ['label' => 'Flota', 'parent_id' => null],
            ['icon' => 'truck', 'route' => null, 'permission' => null, 'orden' => 3]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tractivos.index'],
            ['label' => 'Vehículos', 'icon' => 'truck', 'permission' => 'tractivos.ver', 'orden' => 1, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'motores.index'],
            ['label' => 'Motores', 'icon' => 'cog', 'permission' => 'motores.ver', 'orden' => 2, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'cajas.index'],
            ['label' => 'Cajas', 'icon' => 'cog', 'permission' => 'cajas.ver', 'orden' => 3, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'diferenciales.index'],
            ['label' => 'Diferenciales', 'icon' => 'cog', 'permission' => 'diferenciales.ver', 'orden' => 4, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'baterias.index'],
            ['label' => 'Baterías', 'icon' => 'bolt', 'permission' => 'baterias.ver', 'orden' => 5, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'neumaticos.index'],
            ['label' => 'Neumáticos', 'icon' => 'cog', 'permission' => 'neumaticos.ver', 'orden' => 6, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'lubricantes.index'],
            ['label' => 'Lubricantes', 'icon' => 'droplet', 'permission' => 'lubricantes.ver', 'orden' => 7, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'otros-agregados.index'],
            ['label' => 'Otros Agregados', 'icon' => 'cog', 'permission' => 'otros-agregados.ver', 'orden' => 8, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'energia.index'],
            ['label' => 'Energía', 'icon' => 'bolt', 'permission' => 'energia.ver', 'orden' => 9, 'parent_id' => $flota->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'taller.index'],
            ['label' => 'Taller', 'icon' => 'wrench', 'permission' => 'taller.ver', 'orden' => 4]
        );

        $comercial = MenuItem::firstOrCreate(
            ['label' => 'Comercial', 'parent_id' => null],
            ['icon' => 'briefcase', 'route' => null, 'permission' => null, 'orden' => 5]
        );

        MenuItem::firstOrCreate(
            ['route' => 'clientes.index'],
            ['label' => 'Clientes', 'icon' => 'users', 'permission' => 'clientes.ver', 'orden' => 1, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'lugares.index'],
            ['label' => 'Lugares', 'icon' => 'map-marker', 'permission' => 'lugares.ver', 'orden' => 2, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'distancias.index'],
            ['label' => 'Distancias', 'icon' => 'arrows-alt', 'permission' => 'distancias.ver', 'orden' => 3, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'acuerdos.index'],
            ['label' => 'Acuerdos', 'icon' => 'file-invoice', 'permission' => 'acuerdos.ver', 'orden' => 4, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'solicitudes.index'],
            ['label' => 'Solicitudes', 'icon' => 'envelope', 'permission' => 'solicitudes.ver', 'orden' => 5, 'parent_id' => $comercial->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'giros.index'],
            ['label' => 'Cartas Porte', 'icon' => 'file', 'permission' => 'giros.ver', 'orden' => 6, 'parent_id' => $comercial->id]
        );

        $facturacion = MenuItem::firstOrCreate(
            ['label' => 'Facturación', 'parent_id' => null],
            ['icon' => 'file-invoice', 'route' => null, 'permission' => null, 'orden' => 6]
        );

        MenuItem::firstOrCreate(
            ['route' => 'facturas.index'],
            ['label' => 'Facturas', 'icon' => 'file-invoice', 'permission' => 'facturas.ver', 'orden' => 1, 'parent_id' => $facturacion->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'prefacturas.index'],
            ['label' => 'Prefacturas', 'icon' => 'file-edit', 'permission' => 'prefacturas.ver', 'orden' => 2, 'parent_id' => $facturacion->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipo-ingresos.index'],
            ['label' => 'Tipos de Ingreso', 'icon' => 'tag', 'permission' => 'tipo-ingresos.ver', 'orden' => 3, 'parent_id' => $facturacion->id]
        );

        $rrhh = MenuItem::firstOrCreate(
            ['label' => 'RRHH', 'parent_id' => null],
            ['icon' => 'users', 'route' => null, 'permission' => null, 'orden' => 7]
        );

        MenuItem::firstOrCreate(
            ['route' => 'bolsa.index'],
            ['label' => 'Bolsa', 'icon' => 'user', 'permission' => 'bolsa.ver', 'orden' => 1, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'plantilla.index'],
            ['label' => 'Plantilla', 'icon' => 'table', 'permission' => 'plantilla.ver', 'orden' => 2, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'historial-movimientos.index'],
            ['label' => 'Historial', 'icon' => 'history', 'permission' => 'historial-movimientos.ver', 'orden' => 3, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-incidencias.index'],
            ['label' => 'Tipos Incidencias', 'icon' => 'exclamation-triangle', 'permission' => 'tipos-incidencias.ver', 'orden' => 4, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-penalizaciones.index'],
            ['label' => 'Tipos Penalizaciones', 'icon' => 'ban', 'permission' => 'tipos-penalizaciones.ver', 'orden' => 5, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-contratos.index'],
            ['label' => 'Tipos Contratos', 'icon' => 'file', 'permission' => 'tipos-contratos.ver', 'orden' => 6, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-sistemas-pago.index'],
            ['label' => 'Sistemas de Pago', 'icon' => 'dollar', 'permission' => 'tipos-sistemas-pago.ver', 'orden' => 7, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-pagos-adicionales.index'],
            ['label' => 'Pagos Adicionales', 'icon' => 'plus-circle', 'permission' => 'tipos-pagos-adicionales.ver', 'orden' => 8, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-tasas.index'],
            ['label' => 'Tipos Tasas', 'icon' => 'percentage', 'permission' => 'tipos-tasas.ver', 'orden' => 9, 'parent_id' => $rrhh->id]
        );

        // RRHH - Tablas faltantes (Fase 5.5 parte 3)
        MenuItem::firstOrCreate(
            ['route' => 'salarios.index'],
            ['label' => 'Salarios', 'icon' => 'dollar', 'permission' => 'salarios.ver', 'orden' => 10, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'salarios-administrativos.index'],
            ['label' => 'Salarios Admin.', 'icon' => 'briefcase', 'permission' => 'salarios-administrativos.ver', 'orden' => 11, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'provincias.index'],
            ['label' => 'Provincias', 'icon' => 'map', 'permission' => 'provincias.ver', 'orden' => 12, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'municipios.index'],
            ['label' => 'Municipios', 'icon' => 'map-marker', 'permission' => 'municipios.ver', 'orden' => 13, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'osdes.index'],
            ['label' => 'OSDEs', 'icon' => 'building', 'permission' => 'osdes.ver', 'orden' => 14, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'firmas.index'],
            ['label' => 'Firmas', 'icon' => 'signature', 'permission' => 'firmas.ver', 'orden' => 15, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'meses.index'],
            ['label' => 'Meses', 'icon' => 'calendar', 'permission' => 'meses.ver', 'orden' => 16, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'fondos-tiempo.index'],
            ['label' => 'Fondos Tiempo', 'icon' => 'clock', 'permission' => 'fondos-tiempo.ver', 'orden' => 17, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'medios-proteccion.index'],
            ['label' => 'Medios Protección', 'icon' => 'shield', 'permission' => 'medios-proteccion.ver', 'orden' => 18, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-medios-cargo.index'],
            ['label' => 'Medios x Cargo', 'icon' => 'link', 'permission' => 'tipos-medios-cargo.ver', 'orden' => 19, 'parent_id' => $rrhh->id]
        );

        // RRHH - Catálogos pequeños
        MenuItem::firstOrCreate(
            ['route' => 'tipos-calificadores.index'],
            ['label' => 'Tipos Calificadores', 'icon' => 'star', 'permission' => 'tipos-calificadores.ver', 'orden' => 20, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-causas-laborales.index'],
            ['label' => 'Causas Laborales', 'icon' => 'question-circle', 'permission' => 'tipos-causas-laborales.ver', 'orden' => 21, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-causas-baja.index'],
            ['label' => 'Causas Baja', 'icon' => 'times-circle', 'permission' => 'tipos-causas-baja.ver', 'orden' => 22, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-causas-movimiento.index'],
            ['label' => 'Causas Movimiento', 'icon' => 'arrows-alt', 'permission' => 'tipos-causas-movimiento.ver', 'orden' => 23, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-clasificacion-laboral.index'],
            ['label' => 'Clasif. Laboral', 'icon' => 'tags', 'permission' => 'tipos-clasificacion-laboral.ver', 'orden' => 24, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-color-piel.index'],
            ['label' => 'Color Piel', 'icon' => 'palette', 'permission' => 'tipos-color-piel.ver', 'orden' => 25, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-deducciones.index'],
            ['label' => 'Deducciones', 'icon' => 'minus-circle', 'permission' => 'tipos-deducciones.ver', 'orden' => 26, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-especialidad.index'],
            ['label' => 'Especialidad', 'icon' => 'graduation-cap', 'permission' => 'tipos-especialidad.ver', 'orden' => 27, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-estado-civil.index'],
            ['label' => 'Estado Civil', 'icon' => 'users', 'permission' => 'tipos-estado-civil.ver', 'orden' => 28, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-grupo-horario.index'],
            ['label' => 'Grupo Horario', 'icon' => 'clock', 'permission' => 'tipos-grupo-horario.ver', 'orden' => 29, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-integracion-politica.index'],
            ['label' => 'Integr. Política', 'icon' => 'flag', 'permission' => 'tipos-integracion-politica.ver', 'orden' => 30, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-medios-proteccion.index'],
            ['label' => 'Medios Protección', 'icon' => 'shield', 'permission' => 'tipos-medios-proteccion.ver', 'orden' => 31, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-nivel-educacion.index'],
            ['label' => 'Nivel Educación', 'icon' => 'book', 'permission' => 'tipos-nivel-educacion.ver', 'orden' => 32, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-plantillas.index'],
            ['label' => 'Tipos Plantilla', 'icon' => 'file', 'permission' => 'tipos-plantillas.ver', 'orden' => 33, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-sexo.index'],
            ['label' => 'Tipos Sexo', 'icon' => 'venus-mars', 'permission' => 'tipos-sexo.ver', 'orden' => 34, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-tallas.index'],
            ['label' => 'Tipos Tallas', 'icon' => 'ruler', 'permission' => 'tipos-tallas.ver', 'orden' => 35, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-ubicacion-defensa.index'],
            ['label' => 'Ubic. Defensa', 'icon' => 'map-pin', 'permission' => 'tipos-ubicacion-defensa.ver', 'orden' => 36, 'parent_id' => $rrhh->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'perfiles-rh.index'],
            ['label' => 'Perfiles RH', 'icon' => 'id-card', 'permission' => 'perfiles-rh.ver', 'orden' => 37, 'parent_id' => $rrhh->id]
        );

        $contabilidad = MenuItem::firstOrCreate(
            ['label' => 'Contabilidad', 'parent_id' => null],
            ['icon' => 'book', 'route' => null, 'permission' => null, 'orden' => 8]
        );

        MenuItem::firstOrCreate(
            ['route' => 'conciliaciones.index'],
            ['label' => 'Conciliaciones', 'icon' => 'check-circle', 'permission' => 'conciliaciones.ver', 'orden' => 1, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-conceptos.index'],
            ['label' => 'Tipos Conceptos', 'icon' => 'tag', 'permission' => 'tipos-conceptos.ver', 'orden' => 2, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'otros-gastos.index'],
            ['label' => 'Otros Gastos', 'icon' => 'dollar', 'permission' => 'otros-gastos.ver', 'orden' => 3, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'combustible-cargas.index'],
            ['label' => 'Carga Combustible', 'icon' => 'fuel', 'permission' => 'combustible-cargas.ver', 'orden' => 4, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'combustible-descargas.index'],
            ['label' => 'Descarga Combustible', 'icon' => 'download', 'permission' => 'combustible-descargas.ver', 'orden' => 5, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'inventario.index'],
            ['label' => 'Inventario', 'icon' => 'box', 'permission' => 'inventario.ver', 'orden' => 6, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'vales.index'],
            ['label' => 'Vales', 'icon' => 'ticket', 'permission' => 'vales.ver', 'orden' => 7, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'servicentros.index'],
            ['label' => 'Servicentros', 'icon' => 'map', 'permission' => 'servicentros.ver', 'orden' => 8, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'tipos-documentos.index'],
            ['label' => 'Tipos Documentos', 'icon' => 'file', 'permission' => 'tipos-documentos.ver', 'orden' => 9, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'firmas-autorizadas.index'],
            ['label' => 'Firmas Autorizadas', 'icon' => 'pencil', 'permission' => 'firmas-autorizadas.ver', 'orden' => 10, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'reportes-costos.index'],
            ['label' => 'Reportes Costos', 'icon' => 'chart-line', 'permission' => 'reportes-costos.ver', 'orden' => 11, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'estados-tarjetas.index'],
            ['label' => 'Estados Tarjetas', 'icon' => 'credit-card', 'permission' => 'estados-tarjetas.ver', 'orden' => 12, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'detalles-carga-combustible.index'],
            ['label' => 'Detalles Carga Comb.', 'icon' => 'list', 'permission' => 'detalles-carga-combustible.ver', 'orden' => 13, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'combustibles-lubricantes.index'],
            ['label' => 'Comb. Lubricantes', 'icon' => 'oil', 'permission' => 'combustibles-lubricantes.ver', 'orden' => 14, 'parent_id' => $contabilidad->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'pagos.index'],
            ['label' => 'Pagos', 'icon' => 'money-bill', 'permission' => 'pagos.ver', 'orden' => 15, 'parent_id' => $contabilidad->id]
        );

        $catalogos = MenuItem::firstOrCreate(
            ['label' => 'Catálogos', 'parent_id' => null],
            ['icon' => 'bookmark', 'route' => null, 'permission' => null, 'orden' => 9]
        );

        $catItems = [
            ['marcas.index', 'Marcas', 'tag', 'marcas.ver', 1],
            ['modelos.index', 'Modelos', 'cog', 'modelos.ver', 2],
            ['paises.index', 'Países', 'globe', 'paises.ver', 3],
            ['grupos.index', 'Grupos', 'objectives', 'grupos.ver', 4],
            ['colores.index', 'Colores', 'palette', 'colores.ver', 5],
            ['talleres.index', 'Talleres', 'wrench', 'talleres.ver', 6],
            ['naves.index', 'Naves', 'building', 'naves.ver', 7],
            ['vallas.index', 'Vallas', 'th-large', 'vallas.ver', 8],
            ['destinos-agregados.index', 'Destinos Agregados', 'map', 'destinos-agregados.ver', 9],
            ['medidas-neumaticos.index', 'Medidas Neumáticos', 'ruler', 'medidas-neumaticos.ver', 10],
            ['tipos-combustibles.index', 'Tipos Combustible', 'fuel', 'tipos-combustibles.ver', 11],
            ['tipos-equipos.index', 'Tipos Equipo', 'box', 'tipos-equipos.ver', 12],
            ['tipos-agregados.index', 'Tipos Agregados', 'layer', 'tipos-agregados.ver', 13],
            ['tipos-neumaticos.index', 'Tipos Neumáticos', 'circle', 'tipos-neumaticos.ver', 14],
            ['posiciones-neumaticos.index', 'Posiciones Neumáticos', 'arrows-alt', 'posiciones-neumaticos.ver', 15],
            ['consecutivos.index', 'Consecutivos', 'list', 'consecutivos.ver', 16],
            ['tipos-servicios.index', 'Tipos Servicio', 'briefcase', 'tipos-servicios.ver', 17],
            ['tipos-gastos.index', 'Tipos Gasto', 'dollar', 'tipos-gastos.ver', 18],
            ['embalajes.index', 'Embalajes', 'box', 'embalajes.ver', 19],
            ['buques.index', 'Buques', 'ship', 'buques.ver', 20],
            ['navieras.index', 'Navieras', 'anchor', 'navieras.ver', 21],
            ['organismos.index', 'Organismos', 'building', 'organismos.ver', 22],
            ['categorias-cargo.index', 'Categorías Cargo', 'tags', 'categorias-cargo.ver', 23],
            ['grupos-escala.index', 'Grupos Escala', 'chart-bar', 'grupos-escala.ver', 24],
        ];

        foreach ($catItems as [$route, $label, $icon, $perm, $orden]) {
            MenuItem::firstOrCreate(
                ['route' => $route],
                ['label' => $label, 'icon' => $icon, 'permission' => $perm, 'orden' => $orden, 'parent_id' => $catalogos->id]
            );
        }

        $reportes = MenuItem::firstOrCreate(
            ['label' => 'Reportes', 'parent_id' => null],
            ['icon' => 'file-pdf', 'route' => null, 'permission' => 'reportes.ver', 'orden' => 10]
        );

        MenuItem::firstOrCreate(
            ['route' => 'reportes.marcas'],
            ['label' => 'Listado de Marcas', 'icon' => 'tag', 'permission' => 'reportes.generar', 'orden' => 1, 'parent_id' => $reportes->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'reportes.modelos'],
            ['label' => 'Listado de Modelos', 'icon' => 'cog', 'permission' => 'reportes.generar', 'orden' => 2, 'parent_id' => $reportes->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'reportes.paises'],
            ['label' => 'Listado de Países', 'icon' => 'globe', 'permission' => 'reportes.generar', 'orden' => 3, 'parent_id' => $reportes->id]
        );

        $administracion = MenuItem::firstOrCreate(
            ['label' => 'Administración', 'parent_id' => null],
            ['icon' => 'cog', 'route' => null, 'permission' => null, 'orden' => 90]
        );

        MenuItem::firstOrCreate(
            ['route' => 'usuarios.index'],
            ['label' => 'Usuarios', 'icon' => 'users', 'permission' => 'usuarios.ver', 'orden' => 1, 'parent_id' => $administracion->id]
        );

        MenuItem::firstOrCreate(
            ['route' => 'perfiles.index'],
            ['label' => 'Perfiles', 'icon' => 'shield', 'permission' => 'perfiles.ver', 'orden' => 2, 'parent_id' => $administracion->id]
        );
    }
}
