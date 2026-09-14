<?php

namespace App\Http\Controllers\Api\V1\Comercial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LugareResource;
use App\Models\Lugare;
use Illuminate\Http\Request;

/**
 * Comercial · Lugares (API móvil). Catálogo global (sin scoping por entidad).
 */
class LugareController extends Controller
{
    public function index(Request $request)
    {
        $query = Lugare::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = (string) $request->string('search');
                $q->where(fn ($w) => $w->where('nombre', 'like', "%{$s}%")
                    ->orWhere('provincia', 'like', "%{$s}%")
                    ->orWhere('municipio', 'like', "%{$s}%"));
            })
            ->when($request->has('activo'), fn ($q) => $q->where('activo', $request->boolean('activo')))
            ->orderBy('nombre');

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return LugareResource::collection($query->paginate($perPage));
    }

    public function show(Lugare $lugar)
    {
        return new LugareResource($lugar);
    }
}
