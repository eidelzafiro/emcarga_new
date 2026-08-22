<?php

use Illuminate\Support\Facades\Session;

if (! function_exists('entidadActivaId')) {
    /**
     * Devuelve el id de la entidad activa del contexto de trabajo
     * (sesión `entidad_activa_id`). Punto único de lectura del valor
     * para evitar dispersión de `entidadActivaId()`.
     *
     * Retorna el valor crudo de la sesión (int|null) sin forzar tipo,
     * para que los llamadores conserven su comportamiento actual
     * (p. ej. `(int) entidadActivaId()` o `entidadActivaId() ?: null`).
     */
    function entidadActivaId(): mixed
    {
        return Session::get('entidad_activa_id');
    }
}
