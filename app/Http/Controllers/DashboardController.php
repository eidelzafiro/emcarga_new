<?php

namespace App\Http\Controllers;

use App\Services\KpiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected KpiService $kpiService,
    ) {}

    public function index(Request $request)
    {
        $kpis = $this->kpiService->calcular();

        return Inertia::render('Dashboard', [
            'title' => 'Dashboard',
            'user' => $request->user(),
            'kpis' => $kpis,
        ]);
    }

    public function kpis()
    {
        return response()->json([
            'kpis' => $this->kpiService->calcular(),
        ]);
    }
}
