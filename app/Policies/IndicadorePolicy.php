<?php

namespace App\Policies;

/**
 * Policy del módulo indicadores (editor de indicadores de explotación por aforo).
 *
 * El modelo sobre el que se autoriza es `App\Models\Aforo`; esta policy se
 * registra explícitamente para ese modelo en `AppServiceProvider` y delega en
 * los permisos Spatie `indicadores.ver` / `indicadores.editar`.
 */
class IndicadorePolicy extends ModulePolicy
{
    protected string $permissionPrefix = 'indicadores';
}
