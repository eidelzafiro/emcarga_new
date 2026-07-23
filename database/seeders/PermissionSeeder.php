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

            'otros-agregados.ver', 'otros-agregados.crear', 'otros-agregados.editar', 'otros-agregados.eliminar',

            'energia.ver', 'energia.crear', 'energia.editar', 'energia.eliminar',

            'taller.ver', 'taller.crear', 'taller.editar', 'taller.eliminar',

            'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',

            'lugares.ver', 'lugares.crear', 'lugares.editar', 'lugares.eliminar',

            'distancias.ver', 'distancias.crear', 'distancias.editar', 'distancias.eliminar',

            'acuerdos.ver', 'acuerdos.crear', 'acuerdos.editar', 'acuerdos.eliminar',

            'solicitudes.ver', 'solicitudes.crear', 'solicitudes.editar', 'solicitudes.eliminar',

            'giros.ver', 'giros.crear', 'giros.editar', 'giros.eliminar',

            'facturas.ver', 'facturas.crear', 'facturas.editar', 'facturas.eliminar',
            'facturas.cancelar', 'facturas.refacturar', 'facturas.firmar', 'facturas.cobrar',

            'prefacturas.ver', 'prefacturas.crear', 'prefacturas.editar', 'prefacturas.eliminar',

            'tipo-ingresos.ver', 'tipo-ingresos.crear', 'tipo-ingresos.editar', 'tipo-ingresos.eliminar',

            'bolsa.ver', 'bolsa.crear', 'bolsa.editar', 'bolsa.eliminar',

            'plantilla.ver', 'plantilla.crear', 'plantilla.editar', 'plantilla.eliminar',

            'historial-movimientos.ver', 'historial-movimientos.crear', 'historial-movimientos.editar', 'historial-movimientos.eliminar',

            'tipos-incidencias.ver', 'tipos-incidencias.crear', 'tipos-incidencias.editar', 'tipos-incidencias.eliminar',

            'tipos-penalizaciones.ver', 'tipos-penalizaciones.crear', 'tipos-penalizaciones.editar', 'tipos-penalizaciones.eliminar',

            'tipos-contratos.ver', 'tipos-contratos.crear', 'tipos-contratos.editar', 'tipos-contratos.eliminar',

            'tipos-sistemas-pago.ver', 'tipos-sistemas-pago.crear', 'tipos-sistemas-pago.editar', 'tipos-sistemas-pago.eliminar',

            'tipos-pagos-adicionales.ver', 'tipos-pagos-adicionales.crear', 'tipos-pagos-adicionales.editar', 'tipos-pagos-adicionales.eliminar',

            'tipos-tasas.ver', 'tipos-tasas.crear', 'tipos-tasas.editar', 'tipos-tasas.eliminar',

            'conciliaciones.ver', 'conciliaciones.crear', 'conciliaciones.editar', 'conciliaciones.eliminar',

            'tipos-conceptos.ver', 'tipos-conceptos.crear', 'tipos-conceptos.editar', 'tipos-conceptos.eliminar',

            'otros-gastos.ver', 'otros-gastos.crear', 'otros-gastos.editar', 'otros-gastos.eliminar',

            'combustible-cargas.ver', 'combustible-cargas.crear', 'combustible-cargas.editar', 'combustible-cargas.eliminar',

            'combustible-descargas.ver', 'combustible-descargas.crear', 'combustible-descargas.editar', 'combustible-descargas.eliminar',

            'inventario.ver', 'inventario.crear', 'inventario.editar', 'inventario.eliminar',

            'vales.ver', 'vales.crear', 'vales.editar', 'vales.eliminar',

            // Catálogos y configuración (Fase 5.7)
            'marcas.ver', 'marcas.crear', 'marcas.editar', 'marcas.eliminar',
            'modelos.ver', 'modelos.crear', 'modelos.editar', 'modelos.eliminar',
            'paises.ver', 'paises.crear', 'paises.editar', 'paises.eliminar',
            'naves.ver', 'naves.crear', 'naves.editar', 'naves.eliminar',
            'vallas.ver', 'vallas.crear', 'vallas.editar', 'vallas.eliminar',
            'destinos-agregados.ver', 'destinos-agregados.crear', 'destinos-agregados.editar', 'destinos-agregados.eliminar',
            'medidas-neumaticos.ver', 'medidas-neumaticos.crear', 'medidas-neumaticos.editar', 'medidas-neumaticos.eliminar',
            'tipos-combustibles.ver', 'tipos-combustibles.crear', 'tipos-combustibles.editar', 'tipos-combustibles.eliminar',
            'consecutivos.ver', 'consecutivos.crear', 'consecutivos.editar', 'consecutivos.eliminar',
            'tipos-servicios.ver', 'tipos-servicios.crear', 'tipos-servicios.editar', 'tipos-servicios.eliminar',
            'tipos-gastos.ver', 'tipos-gastos.crear', 'tipos-gastos.editar', 'tipos-gastos.eliminar',
            'grupos.ver', 'grupos.crear', 'grupos.editar', 'grupos.eliminar',
            'colores.ver', 'colores.crear', 'colores.editar', 'colores.eliminar',
            'talleres.ver', 'talleres.crear', 'talleres.editar', 'talleres.eliminar',
            'tipos-equipos.ver', 'tipos-equipos.crear', 'tipos-equipos.editar', 'tipos-equipos.eliminar',
            'tipos-agregados.ver', 'tipos-agregados.crear', 'tipos-agregados.editar', 'tipos-agregados.eliminar',
            'tipos-neumaticos.ver', 'tipos-neumaticos.crear', 'tipos-neumaticos.editar', 'tipos-neumaticos.eliminar',
            'posiciones-neumaticos.ver', 'posiciones-neumaticos.crear', 'posiciones-neumaticos.editar', 'posiciones-neumaticos.eliminar',
            'embalajes.ver', 'embalajes.crear', 'embalajes.editar', 'embalajes.eliminar',
            'buques.ver', 'buques.crear', 'buques.editar', 'buques.eliminar',
            'navieras.ver', 'navieras.crear', 'navieras.editar', 'navieras.eliminar',
            'organismos.ver', 'organismos.crear', 'organismos.editar', 'organismos.eliminar',
            'categorias-cargo.ver', 'categorias-cargo.crear', 'categorias-cargo.editar', 'categorias-cargo.eliminar',
            'grupos-escala.ver', 'grupos-escala.crear', 'grupos-escala.editar', 'grupos-escala.eliminar',

            'reportes.ver', 'reportes.generar',

            'pizarra.ver',

            'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
            'usuarios.desbloquear', 'usuarios.restablecer',

            'perfiles.ver', 'perfiles.editar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Asignación de permisos por rol (perfiles legacy)
        $asignacion = [
            'ADMIN' => $permisos,
            'TECNICA' => [
                'dashboard.ver',
                'tractivos.ver', 'tractivos.crear', 'tractivos.editar', 'tractivos.eliminar',
                'motores.ver', 'motores.crear', 'motores.editar', 'motores.eliminar',
                'cajas.ver', 'cajas.crear', 'cajas.editar', 'cajas.eliminar',
                'diferenciales.ver', 'diferenciales.crear', 'diferenciales.editar', 'diferenciales.eliminar',
                'baterias.ver', 'baterias.crear', 'baterias.editar', 'baterias.eliminar',
                'neumaticos.ver', 'neumaticos.crear', 'neumaticos.editar', 'neumaticos.eliminar',
                'lubricantes.ver', 'lubricantes.crear', 'lubricantes.editar', 'lubricantes.eliminar',
                'otros-agregados.ver', 'otros-agregados.crear', 'otros-agregados.editar', 'otros-agregados.eliminar',
                'energia.ver', 'energia.crear', 'energia.editar', 'energia.eliminar',
                'pizarra.ver',
                'reportes.ver', 'reportes.generar',
                'taller.ver', 'taller.crear', 'taller.editar', 'taller.eliminar',
                // Catálogos técnicos
                'marcas.ver', 'marcas.crear', 'marcas.editar', 'marcas.eliminar',
                'modelos.ver', 'modelos.crear', 'modelos.editar', 'modelos.eliminar',
                'paises.ver', 'paises.crear', 'paises.editar', 'paises.eliminar',
                'naves.ver', 'naves.crear', 'naves.editar', 'naves.eliminar',
                'vallas.ver', 'vallas.crear', 'vallas.editar', 'vallas.eliminar',
                'destinos-agregados.ver', 'destinos-agregados.crear', 'destinos-agregados.editar', 'destinos-agregados.eliminar',
                'medidas-neumaticos.ver', 'medidas-neumaticos.crear', 'medidas-neumaticos.editar', 'medidas-neumaticos.eliminar',
                'tipos-combustibles.ver', 'tipos-combustibles.crear', 'tipos-combustibles.editar', 'tipos-combustibles.eliminar',
                'consecutivos.ver', 'consecutivos.crear', 'consecutivos.editar', 'consecutivos.eliminar',
                'tipos-gastos.ver', 'tipos-gastos.crear', 'tipos-gastos.editar', 'tipos-gastos.eliminar',
                'grupos.ver', 'grupos.crear', 'grupos.editar', 'grupos.eliminar',
                'colores.ver', 'colores.crear', 'colores.editar', 'colores.eliminar',
                'talleres.ver', 'talleres.crear', 'talleres.editar', 'talleres.eliminar',
                'tipos-equipos.ver', 'tipos-equipos.crear', 'tipos-equipos.editar', 'tipos-equipos.eliminar',
                'tipos-agregados.ver', 'tipos-agregados.crear', 'tipos-agregados.editar', 'tipos-agregados.eliminar',
                'tipos-neumaticos.ver', 'tipos-neumaticos.crear', 'tipos-neumaticos.editar', 'tipos-neumaticos.eliminar',
                'posiciones-neumaticos.ver', 'posiciones-neumaticos.crear', 'posiciones-neumaticos.editar', 'posiciones-neumaticos.eliminar',
            ],
            'COMERCIAL' => [
                'dashboard.ver',
                'clientes.ver', 'clientes.crear', 'clientes.editar', 'clientes.eliminar',
                'lugares.ver', 'lugares.crear', 'lugares.editar', 'lugares.eliminar',
                'distancias.ver', 'distancias.crear', 'distancias.editar', 'distancias.eliminar',
                'acuerdos.ver', 'acuerdos.crear', 'acuerdos.editar', 'acuerdos.eliminar',
                'solicitudes.ver', 'solicitudes.crear', 'solicitudes.editar', 'solicitudes.eliminar',
                'giros.ver', 'giros.crear', 'giros.editar', 'giros.eliminar',
                'facturas.ver', 'facturas.crear', 'facturas.editar', 'facturas.eliminar',
                'facturas.cancelar', 'facturas.refacturar', 'facturas.firmar', 'facturas.cobrar',
                'prefacturas.ver', 'prefacturas.crear', 'prefacturas.editar', 'prefacturas.eliminar',
                'tipo-ingresos.ver', 'tipo-ingresos.crear', 'tipo-ingresos.editar', 'tipo-ingresos.eliminar',
                // Catálogos comerciales
                'tipos-servicios.ver', 'tipos-servicios.crear', 'tipos-servicios.editar', 'tipos-servicios.eliminar',
                'embalajes.ver', 'embalajes.crear', 'embalajes.editar', 'embalajes.eliminar',
                'buques.ver', 'buques.crear', 'buques.editar', 'buques.eliminar',
                'navieras.ver', 'navieras.crear', 'navieras.editar', 'navieras.eliminar',
            ],
            'RECHUM' => [
                'dashboard.ver',
                'bolsa.ver', 'bolsa.crear', 'bolsa.editar', 'bolsa.eliminar',
                'plantilla.ver', 'plantilla.crear', 'plantilla.editar', 'plantilla.eliminar',
                'historial-movimientos.ver', 'historial-movimientos.crear', 'historial-movimientos.editar', 'historial-movimientos.eliminar',
                'tipos-incidencias.ver', 'tipos-incidencias.crear', 'tipos-incidencias.editar', 'tipos-incidencias.eliminar',
                'tipos-penalizaciones.ver', 'tipos-penalizaciones.crear', 'tipos-penalizaciones.editar', 'tipos-penalizaciones.eliminar',
                'tipos-contratos.ver', 'tipos-contratos.crear', 'tipos-contratos.editar', 'tipos-contratos.eliminar',
                'tipos-sistemas-pago.ver', 'tipos-sistemas-pago.crear', 'tipos-sistemas-pago.editar', 'tipos-sistemas-pago.eliminar',
                'tipos-pagos-adicionales.ver', 'tipos-pagos-adicionales.crear', 'tipos-pagos-adicionales.editar', 'tipos-pagos-adicionales.eliminar',
                'tipos-tasas.ver', 'tipos-tasas.crear', 'tipos-tasas.editar', 'tipos-tasas.eliminar',
                // Catálogos RRHH
                'organismos.ver', 'organismos.crear', 'organismos.editar', 'organismos.eliminar',
                'categorias-cargo.ver', 'categorias-cargo.crear', 'categorias-cargo.editar', 'categorias-cargo.eliminar',
                'grupos-escala.ver', 'grupos-escala.crear', 'grupos-escala.editar', 'grupos-escala.eliminar',
            ],
            'CONTABILIDAD' => [
                'dashboard.ver',
                'conciliaciones.ver', 'conciliaciones.crear', 'conciliaciones.editar', 'conciliaciones.eliminar',
                'tipos-conceptos.ver', 'tipos-conceptos.crear', 'tipos-conceptos.editar', 'tipos-conceptos.eliminar',
                'otros-gastos.ver', 'otros-gastos.crear', 'otros-gastos.editar', 'otros-gastos.eliminar',
                'combustible-cargas.ver', 'combustible-cargas.crear', 'combustible-cargas.editar', 'combustible-cargas.eliminar',
                'combustible-descargas.ver', 'combustible-descargas.crear', 'combustible-descargas.editar', 'combustible-descargas.eliminar',
                'inventario.ver', 'inventario.crear', 'inventario.editar', 'inventario.eliminar',
                'vales.ver', 'vales.crear', 'vales.editar', 'vales.eliminar',
            ],
            'OPERATIVOS' => ['dashboard.ver'],
        ];

        foreach ($asignacion as $nombreRol => $permisosRol) {
            $rol = Role::firstOrCreate(['name' => $nombreRol]);
            $rol->syncPermissions($permisosRol);
        }
    }
}
