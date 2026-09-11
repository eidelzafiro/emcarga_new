<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Roles heredados del sistema legacy (rh_perfiles) y sus permisos
     * por módulo (modulo.accion). Al migrar cada módulo nuevo en la
     * Fase 5, sus permisos se agregan a esta lista y a su rol.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'dashboard.ver',

            'tractivos.ver', 'tractivos.crear', 'tractivos.editar', 'tractivos.eliminar',

            'motores.ver', 'motores.crear', 'motores.editar', 'motores.eliminar',

            'cajas.ver', 'cajas.crear', 'cajas.editar', 'cajas.eliminar',

            'diferenciales.ver', 'diferenciales.crear', 'diferenciales.editar', 'diferenciales.eliminar',

            'baterias.ver', 'baterias.crear', 'baterias.editar', 'baterias.eliminar',

            'neumaticos.ver', 'neumaticos.crear', 'neumaticos.editar', 'neumaticos.eliminar',

            'lubricantes.ver', 'lubricantes.crear', 'lubricantes.editar', 'lubricantes.eliminar',

            'control-lubricante.ver', 'control-lubricante.crear', 'control-lubricante.editar', 'control-lubricante.eliminar',

            'otros-agregados.ver', 'otros-agregados.crear', 'otros-agregados.editar', 'otros-agregados.eliminar',

            'energia.ver', 'energia.crear', 'energia.editar', 'energia.eliminar',

            'taller.ver', 'taller.crear', 'taller.editar', 'taller.eliminar',

            'tipos-mantenimiento.ver', 'tipos-mantenimiento.crear', 'tipos-mantenimiento.editar', 'tipos-mantenimiento.eliminar',

            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',

            'lugares.ver', 'lugares.crear', 'lugares.editar', 'lugares.eliminar',

            'distancias.ver', 'distancias.crear', 'distancias.editar', 'distancias.eliminar',

            'acuerdos.ver', 'acuerdos.crear', 'acuerdos.editar', 'acuerdos.eliminar',

            'solicitudes.ver', 'solicitudes.crear', 'solicitudes.editar', 'solicitudes.eliminar',

            'carta-porte.ver', 'carta-porte.crear', 'carta-porte.editar', 'carta-porte.eliminar',

            'facturas.ver', 'facturas.crear', 'facturas.editar', 'facturas.eliminar',
            'facturas.cancelar', 'facturas.refacturar', 'facturas.firmar', 'facturas.cobrar',

            'prefacturas.ver', 'prefacturas.crear', 'prefacturas.editar', 'prefacturas.eliminar',


            'bolsa.ver', 'bolsa.crear', 'bolsa.editar', 'bolsa.eliminar',

            'cds.ver', 'cds.crear', 'cds.editar', 'cds.eliminar',

            'plantilla.ver', 'plantilla.crear', 'plantilla.editar', 'plantilla.eliminar',

            'historial-movimientos.ver', 'historial-movimientos.crear', 'historial-movimientos.editar', 'historial-movimientos.eliminar',



            'meses.ver', 'meses.crear', 'meses.editar', 'meses.eliminar',

            'tipos-contratos.ver', 'tipos-contratos.crear', 'tipos-contratos.editar', 'tipos-contratos.eliminar',



            'tipos-tasas.ver', 'tipos-tasas.crear', 'tipos-tasas.editar', 'tipos-tasas.eliminar',

            'conciliaciones.ver', 'conciliaciones.crear', 'conciliaciones.editar', 'conciliaciones.eliminar',

            'tipos-conceptos.ver', 'tipos-conceptos.crear', 'tipos-conceptos.editar', 'tipos-conceptos.eliminar',

            'otros-gastos.ver', 'otros-gastos.crear', 'otros-gastos.editar', 'otros-gastos.eliminar',

            'combustible-cargas.ver', 'combustible-cargas.crear', 'combustible-cargas.editar', 'combustible-cargas.eliminar',

            'tarjetas.ver', 'tarjetas.crear', 'tarjetas.editar', 'tarjetas.eliminar',

            'combustible-descargas.ver', 'combustible-descargas.crear', 'combustible-descargas.editar', 'combustible-descargas.eliminar',

            'inventario.ver', 'inventario.crear', 'inventario.editar', 'inventario.eliminar',

            'vales.ver', 'vales.crear', 'vales.editar', 'vales.eliminar',

            // Catálogos y configuración (Fase 5.7)
            'naves.ver', 'naves.crear', 'naves.editar', 'naves.eliminar',
            'vallas.ver', 'vallas.crear', 'vallas.editar', 'vallas.eliminar',
            'consecutivos.ver', 'consecutivos.crear', 'consecutivos.editar', 'consecutivos.eliminar',
            'talleres.ver', 'talleres.crear', 'talleres.editar', 'talleres.eliminar',
            'grupos-escala.ver', 'grupos-escala.crear', 'grupos-escala.editar', 'grupos-escala.eliminar',
            'cargos.ver', 'cargos.crear', 'cargos.editar', 'cargos.eliminar',
            'areas.ver', 'areas.crear', 'areas.editar', 'areas.eliminar',
            'entidades.ver', 'entidades.crear', 'entidades.editar', 'entidades.eliminar',

            'reportes.ver', 'reportes.generar', 'reportes-nomina.ver', 'reportes-ingresos.ver', 'reportes-tecnico.ver',

            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'usuarios.desbloquear', 'usuarios.restablecer',

            'perfiles.ver', 'perfiles.editar',

            'menus.ver', 'menus.crear', 'menus.editar', 'menus.eliminar', 'menus.admin',

            'catalogo.ver', 'catalogo.crear', 'catalogo.editar', 'catalogo.eliminar',

            'servicentros.ver', 'servicentros.crear', 'servicentros.editar', 'servicentros.eliminar',
            'firmas-autorizadas.ver', 'firmas-autorizadas.crear', 'firmas-autorizadas.editar', 'firmas-autorizadas.eliminar',
            'reportes-costos.ver', 'reportes-costos.crear', 'reportes-costos.editar', 'reportes-costos.eliminar',
            'estados-tarjetas.ver', 'estados-tarjetas.crear', 'estados-tarjetas.editar', 'estados-tarjetas.eliminar',
            'combustibles-lubricantes.ver', 'combustibles-lubricantes.crear', 'combustibles-lubricantes.editar', 'combustibles-lubricantes.eliminar',
            'pagos.ver', 'pagos.crear', 'pagos.editar', 'pagos.eliminar',

            // RRHH - Tablas faltantes
            'osdes.ver', 'osdes.crear', 'osdes.editar', 'osdes.eliminar',
            'firmas.ver', 'firmas.crear', 'firmas.editar', 'firmas.eliminar',
            'fondos-tiempo.ver', 'fondos-tiempo.crear', 'fondos-tiempo.editar', 'fondos-tiempo.eliminar',
                'salarios.ver', 'salarios.crear', 'salarios.editar', 'salarios.eliminar',
                'salarios-choferes.ver',
                'salarios-administrativos.ver', 'salarios-administrativos.crear', 'salarios-administrativos.editar', 'salarios-administrativos.eliminar',
            'tipos-clasificacion-laboral.ver', 'tipos-clasificacion-laboral.crear', 'tipos-clasificacion-laboral.editar', 'tipos-clasificacion-laboral.eliminar',

            // Comercial - Tablas faltantes
            'configuraciones-modelo.ver', 'configuraciones-modelo.crear', 'configuraciones-modelo.editar', 'configuraciones-modelo.eliminar',
            'tipos-cargas-reporte.ver', 'tipos-cargas-reporte.crear', 'tipos-cargas-reporte.editar', 'tipos-cargas-reporte.eliminar',
            'hojas-ruta.ver', 'hojas-ruta.crear', 'hojas-ruta.editar', 'hojas-ruta.eliminar',
            'alertas.ver', 'alertas.crear', 'alertas.editar', 'alertas.eliminar',
            'indicadores.ver', 'indicadores.crear', 'indicadores.editar', 'indicadores.eliminar',
            'demandas.ver', 'demandas.crear', 'demandas.editar', 'demandas.eliminar',
            'tarifas.ver', 'tarifas.crear', 'tarifas.editar', 'tarifas.eliminar',
            'otros-ingresos-pre.ver', 'otros-ingresos-pre.crear', 'otros-ingresos-pre.editar', 'otros-ingresos-pre.eliminar',

            // Técnica - Tablas faltantes
            'arrastres.ver', 'arrastres.crear', 'arrastres.editar', 'arrastres.eliminar',
            'tipos-tractivos.ver', 'tipos-tractivos.crear', 'tipos-tractivos.editar', 'tipos-tractivos.eliminar',
            'tipos-arrastres.ver', 'tipos-arrastres.crear', 'tipos-arrastres.editar', 'tipos-arrastres.eliminar',
            'tipos-equipos.ver', 'tipos-equipos.crear', 'tipos-equipos.editar', 'tipos-equipos.eliminar',
            'tipo-vehiculos.ver', 'tipo-vehiculos.crear', 'tipo-vehiculos.editar', 'tipo-vehiculos.eliminar',
            'balances-electricos.ver', 'balances-electricos.crear', 'balances-electricos.editar', 'balances-electricos.eliminar',
            'historial-tractivos.ver', 'historial-tractivos.crear', 'historial-tractivos.editar', 'historial-tractivos.eliminar',
            'locales-electricos.ver', 'locales-electricos.crear', 'locales-electricos.editar', 'locales-electricos.eliminar',

            // ATM - Inventario/Tarjetero
            'tarjetero.ver', 'tarjetero.crear', 'tarjetero.editar', 'tarjetero.eliminar',

            'movimientos-inventario.ver', 'movimientos-inventario.crear', 'movimientos-inventario.editar', 'movimientos-inventario.eliminar',

            // Comercial - Tablas faltantes
            'contenedores.ver', 'contenedores.crear', 'contenedores.editar', 'contenedores.eliminar',
            'categorias-productos.ver', 'categorias-productos.crear', 'categorias-productos.editar', 'categorias-productos.eliminar',
            'productos.ver', 'productos.crear', 'productos.editar', 'productos.eliminar',

            // Misc
            'choferes.ver', 'choferes.crear', 'choferes.editar', 'choferes.eliminar',
            'empleados.ver', 'empleados.crear', 'empleados.editar', 'empleados.eliminar',
            'devoluciones.ver', 'devoluciones.crear', 'devoluciones.editar', 'devoluciones.eliminar',

            'estadisticas-explotacion.ver', 'estadisticas-explotacion.crear', 'estadisticas-explotacion.editar', 'estadisticas-explotacion.eliminar',
            'registro-ordenes-taller.ver', 'registro-ordenes-taller.crear', 'registro-ordenes-taller.editar', 'registro-ordenes-taller.eliminar',

            // Nómina (2026-08-18): incidencias, penalizaciones, dietas y reembolsos
            'incidencias.ver', 'incidencias.crear', 'incidencias.editar', 'incidencias.eliminar',
            'penalizaciones.ver', 'penalizaciones.crear', 'penalizaciones.editar', 'penalizaciones.eliminar',
            'dietas.ver', 'dietas.crear', 'dietas.editar', 'dietas.eliminar',
            'reembolsos.ver', 'reembolsos.crear', 'reembolsos.editar', 'reembolsos.eliminar',
        ];

        // Permisos por tipo del catálogo unificado (catalogo.{tipo}.{accion}).
        // Cada tipo es visible/editable de forma independiente por rol.
        $catalogo = $this->catalogoPorRol();
        $permisos = array_merge($permisos, $catalogo['todos']);

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Asignación de permisos por rol (perfiles legacy)
        $asignacion = [
            'SUPERADMIN' => $permisos,
            'DIRECTIVOS' => [
                'dashboard.ver',
                'bolsa.ver',
            ],
            'TECNICA' => [
                'dashboard.ver',
                'tractivos.ver', 'tractivos.crear', 'tractivos.editar', 'tractivos.eliminar',
                'motores.ver', 'motores.crear', 'motores.editar', 'motores.eliminar',
                'cajas.ver', 'cajas.crear', 'cajas.editar', 'cajas.eliminar',
                'diferenciales.ver', 'diferenciales.crear', 'diferenciales.editar', 'diferenciales.eliminar',
                'baterias.ver', 'baterias.crear', 'baterias.editar', 'baterias.eliminar',
                'neumaticos.ver', 'neumaticos.crear', 'neumaticos.editar', 'neumaticos.eliminar',
            'lubricantes.ver', 'lubricantes.crear', 'lubricantes.editar', 'lubricantes.eliminar',

            'control-lubricante.ver', 'control-lubricante.crear', 'control-lubricante.editar', 'control-lubricante.eliminar',

                'otros-agregados.ver', 'otros-agregados.crear', 'otros-agregados.editar', 'otros-agregados.eliminar',
                'energia.ver', 'energia.crear', 'energia.editar', 'energia.eliminar',

            'reportes-tecnico.ver',
            'taller.ver', 'taller.crear', 'taller.editar', 'taller.eliminar',

            'tipos-mantenimiento.ver', 'tipos-mantenimiento.crear', 'tipos-mantenimiento.editar', 'tipos-mantenimiento.eliminar',
                // Catálogos técnicos
                'naves.ver', 'naves.crear', 'naves.editar', 'naves.eliminar',
                'vallas.ver', 'vallas.crear', 'vallas.editar', 'vallas.eliminar',
                'consecutivos.ver', 'consecutivos.crear', 'consecutivos.editar', 'consecutivos.eliminar',
                'talleres.ver', 'talleres.crear', 'talleres.editar', 'talleres.eliminar',
                // Técnica - Tablas faltantes
                                 'arrastres.ver', 'arrastres.crear', 'arrastres.editar', 'arrastres.eliminar',
                'tipos-tractivos.ver', 'tipos-tractivos.crear', 'tipos-tractivos.editar', 'tipos-tractivos.eliminar',
                'tipos-arrastres.ver', 'tipos-arrastres.crear', 'tipos-arrastres.editar', 'tipos-arrastres.eliminar',
                'tipos-equipos.ver', 'tipos-equipos.crear', 'tipos-equipos.editar', 'tipos-equipos.eliminar',
                'tipo-vehiculos.ver', 'tipo-vehiculos.crear', 'tipo-vehiculos.editar', 'tipo-vehiculos.eliminar',
                'balances-electricos.ver', 'balances-electricos.crear', 'balances-electricos.editar', 'balances-electricos.eliminar',

                'historial-tractivos.ver', 'historial-tractivos.crear', 'historial-tractivos.editar', 'historial-tractivos.eliminar',
                'locales-electricos.ver', 'locales-electricos.crear', 'locales-electricos.editar', 'locales-electricos.eliminar',

                'tarjetero.ver', 'tarjetero.crear', 'tarjetero.editar', 'tarjetero.eliminar',

                'movimientos-inventario.ver', 'movimientos-inventario.crear', 'movimientos-inventario.editar', 'movimientos-inventario.eliminar',

                'choferes.ver', 'choferes.crear', 'choferes.editar', 'choferes.eliminar',
                'estadisticas-explotacion.ver', 'estadisticas-explotacion.crear', 'estadisticas-explotacion.editar', 'estadisticas-explotacion.eliminar',
                'registro-ordenes-taller.ver', 'registro-ordenes-taller.crear', 'registro-ordenes-taller.editar', 'registro-ordenes-taller.eliminar',
            ],
            'COMERCIAL' => [
                'dashboard.ver',
                'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
                'lugares.ver', 'lugares.crear', 'lugares.editar', 'lugares.eliminar',
                'distancias.ver', 'distancias.crear', 'distancias.editar', 'distancias.eliminar',
                'acuerdos.ver', 'acuerdos.crear', 'acuerdos.editar', 'acuerdos.eliminar',
                'solicitudes.ver', 'solicitudes.crear', 'solicitudes.editar', 'solicitudes.eliminar',
                'carta-porte.ver', 'carta-porte.crear', 'carta-porte.editar', 'carta-porte.eliminar',
                'facturas.ver', 'facturas.crear', 'facturas.editar', 'facturas.eliminar',
                'facturas.cancelar', 'facturas.refacturar', 'facturas.firmar', 'facturas.cobrar',
                'prefacturas.ver', 'prefacturas.crear', 'prefacturas.editar', 'prefacturas.eliminar',
                // Catálogos comerciales
                // Comercial - Tablas faltantes
                'configuraciones-modelo.ver', 'configuraciones-modelo.crear', 'configuraciones-modelo.editar', 'configuraciones-modelo.eliminar',
                'tipos-cargas-reporte.ver', 'tipos-cargas-reporte.crear', 'tipos-cargas-reporte.editar', 'tipos-cargas-reporte.eliminar',
                'hojas-ruta.ver', 'hojas-ruta.crear', 'hojas-ruta.editar', 'hojas-ruta.eliminar',
                'alertas.ver', 'alertas.crear', 'alertas.editar', 'alertas.eliminar',
                'indicadores.ver', 'indicadores.crear', 'indicadores.editar', 'indicadores.eliminar',
                'demandas.ver', 'demandas.crear', 'demandas.editar', 'demandas.eliminar',
                'tarifas.ver', 'tarifas.crear', 'tarifas.editar', 'tarifas.eliminar',
                'otros-ingresos-pre.ver', 'otros-ingresos-pre.crear', 'otros-ingresos-pre.editar', 'otros-ingresos-pre.eliminar',
                // Comercial - Tablas faltantes parte 2

                'contenedores.ver', 'contenedores.crear', 'contenedores.editar', 'contenedores.eliminar',
                'categorias-productos.ver', 'categorias-productos.crear', 'categorias-productos.editar', 'categorias-productos.eliminar',
                'productos.ver', 'productos.crear', 'productos.editar', 'productos.eliminar',

                'devoluciones.ver', 'devoluciones.crear', 'devoluciones.editar', 'devoluciones.eliminar',

            ],
            'RECHUM' => [
                'dashboard.ver',
                'reportes.ver', 'reportes.generar', 'reportes-nomina.ver',
                'bolsa.ver', 'bolsa.crear', 'bolsa.editar', 'bolsa.eliminar',
                'plantilla.ver', 'plantilla.crear', 'plantilla.editar', 'plantilla.eliminar',
                'historial-movimientos.ver', 'historial-movimientos.crear', 'historial-movimientos.editar', 'historial-movimientos.eliminar',
                'tipos-contratos.ver', 'tipos-contratos.crear', 'tipos-contratos.editar', 'tipos-contratos.eliminar',
            'tipos-tasas.ver', 'tipos-tasas.crear', 'tipos-tasas.editar', 'tipos-tasas.eliminar',

            'cds.ver', 'cds.crear', 'cds.editar', 'cds.eliminar',

                // Catálogos RRHH
                'grupos-escala.ver', 'grupos-escala.crear', 'grupos-escala.editar', 'grupos-escala.eliminar',
                'cargos.ver', 'cargos.crear', 'cargos.editar', 'cargos.eliminar',
                'areas.ver', 'areas.crear', 'areas.editar', 'areas.eliminar',
                // RRHH - Tablas faltantes
                'osdes.ver', 'osdes.crear', 'osdes.editar', 'osdes.eliminar',
                'firmas.ver', 'firmas.crear', 'firmas.editar', 'firmas.eliminar',
                'fondos-tiempo.ver', 'fondos-tiempo.crear', 'fondos-tiempo.editar', 'fondos-tiempo.eliminar',
            'salarios.ver', 'salarios.crear', 'salarios.editar', 'salarios.eliminar',

            'salarios-choferes.ver',
                'salarios-administrativos.ver', 'salarios-administrativos.crear', 'salarios-administrativos.editar', 'salarios-administrativos.eliminar',
                'meses.ver', 'meses.crear', 'meses.editar', 'meses.eliminar',
                'tipos-clasificacion-laboral.ver', 'tipos-clasificacion-laboral.crear', 'tipos-clasificacion-laboral.editar', 'tipos-clasificacion-laboral.eliminar',

                // RRHH - Empleados
                'empleados.ver', 'empleados.crear', 'empleados.editar', 'empleados.eliminar',
                // Nómina (2026-08-18)
                'incidencias.ver', 'incidencias.crear', 'incidencias.editar', 'incidencias.eliminar',
                'penalizaciones.ver', 'penalizaciones.crear', 'penalizaciones.editar', 'penalizaciones.eliminar',
            'dietas.ver', 'dietas.crear', 'dietas.editar', 'dietas.eliminar',
            'reembolsos.ver', 'reembolsos.crear', 'reembolsos.editar', 'reembolsos.eliminar',
            ],
            'CONTABILIDAD' => [
                'dashboard.ver',
                'reportes-ingresos.ver',
                'conciliaciones.ver', 'conciliaciones.crear', 'conciliaciones.editar', 'conciliaciones.eliminar',
                'tipos-conceptos.ver', 'tipos-conceptos.crear', 'tipos-conceptos.editar', 'tipos-conceptos.eliminar',
                'otros-gastos.ver', 'otros-gastos.crear', 'otros-gastos.editar', 'otros-gastos.eliminar',
                'combustible-cargas.ver', 'combustible-cargas.crear', 'combustible-cargas.editar', 'combustible-cargas.eliminar',
                'tarjetas.ver', 'tarjetas.crear', 'tarjetas.editar', 'tarjetas.eliminar',
                'combustible-descargas.ver', 'combustible-descargas.crear', 'combustible-descargas.editar', 'combustible-descargas.eliminar',
                'inventario.ver', 'inventario.crear', 'inventario.editar', 'inventario.eliminar',
                'vales.ver', 'vales.crear', 'vales.editar', 'vales.eliminar',
                'servicentros.ver', 'servicentros.crear', 'servicentros.editar', 'servicentros.eliminar',
                'firmas-autorizadas.ver', 'firmas-autorizadas.crear', 'firmas-autorizadas.editar', 'firmas-autorizadas.eliminar',
                'reportes-costos.ver', 'reportes-costos.crear', 'reportes-costos.editar', 'reportes-costos.eliminar',
                'estados-tarjetas.ver', 'estados-tarjetas.crear', 'estados-tarjetas.editar', 'estados-tarjetas.eliminar',

                'combustibles-lubricantes.ver', 'combustibles-lubricantes.crear', 'combustibles-lubricantes.editar', 'combustibles-lubricantes.eliminar',
                'pagos.ver', 'pagos.crear', 'pagos.editar', 'pagos.eliminar',
                'reembolsos.ver', 'reembolsos.crear', 'reembolsos.editar', 'reembolsos.eliminar',
                'dietas.ver', 'dietas.crear', 'dietas.editar', 'dietas.eliminar',
            ],
            'OPERATIVOS' => [
                'dashboard.ver',
                'choferes.ver', 'choferes.crear', 'choferes.editar', 'choferes.eliminar',

                'estadisticas-explotacion.ver', 'estadisticas-explotacion.crear', 'estadisticas-explotacion.editar', 'estadisticas-explotacion.eliminar',
            ],

            'CONFIGURACIONES' => [
                'dashboard.ver',

                'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
                'usuarios.desbloquear', 'usuarios.restablecer',

                'perfiles.ver', 'perfiles.editar',

                'menus.ver', 'menus.crear', 'menus.editar', 'menus.eliminar',
                'catalogo.ver', 'catalogo.crear', 'catalogo.editar', 'catalogo.eliminar',

                'entidades.ver', 'entidades.crear', 'entidades.editar', 'entidades.eliminar',

                'configuraciones-modelo.ver', 'configuraciones-modelo.crear', 'configuraciones-modelo.editar', 'configuraciones-modelo.eliminar',
            ],
        ];

        foreach ($asignacion as $nombreRol => $permisosRol) {
            $rol = Role::firstOrCreate(['name' => $nombreRol]);
            $permisosRol = array_merge($permisosRol, $catalogo['porRol'][$nombreRol] ?? []);
            $rol->syncPermissions(array_values(array_unique($permisosRol)));
        }
    }

    /**
     * Permisos del catálogo unificado por tipo de catálogo.
     *
     * - 'todos': TODOS los permisos catalogo.{tipo}.{ver|crear|editar|eliminar}
     *   (para crear los permisos y asignarlos a SUPERADMIN/CONFIGURACIONES).
     * - 'porRol': [rol => [catalogo.{tipo}.ver, ...]] según la agrupación del
     *   tipo (Técnica → TECNICA, Comercial → COMERCIAL, etc.).
     */
    private function catalogoPorRol(): array
    {
        $agrupacionARol = [
            'Técnica' => 'TECNICA',
            'Comercial' => 'COMERCIAL',
            'Contabilidad' => 'CONTABILIDAD',
            'RRHH' => 'RECHUM',
        ];

        $todos = [];
        $porRol = [];

        foreach (\App\Models\CatalogoTipo::all(['tipo', 'agrupacion']) as $ct) {
            $tipo = $ct->tipo;
            $base = "catalogo.{$tipo}";
            foreach (['ver', 'crear', 'editar', 'eliminar'] as $accion) {
                $todos[] = "{$base}.{$accion}";
            }

            $rol = $agrupacionARol[$ct->agrupacion] ?? null;
            if ($rol) {
                $porRol[$rol][] = "{$base}.ver";
            }
        }

        // CONFIGURACIONES administra todo el catálogo (ver + editar).
        $porRol['CONFIGURACIONES'] = array_merge($porRol['CONFIGURACIONES'] ?? [], $todos);

        return [
            'todos' => $todos,
            'porRol' => $porRol,
        ];
    }
}
