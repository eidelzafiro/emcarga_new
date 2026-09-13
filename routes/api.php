<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Zafiro (móvil)
|--------------------------------------------------------------------------
| Todas las rutas van versionadas bajo /api/v1. Ver docs/PLAN_API_MOVIL.md.
*/

Route::prefix('v1')->group(function () {
    require __DIR__.'/api_v1.php';
});
