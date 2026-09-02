<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Services\DashboardContabilidadService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContabilidadController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', Factura::class);

        $service = app(DashboardContabilidadService::class);

        return Inertia::render('Contabilidad/Dashboard', array_merge(
            ['title' => 'Dashboard Contabilidad'],
            $service->datos()
        ));
    }
}
