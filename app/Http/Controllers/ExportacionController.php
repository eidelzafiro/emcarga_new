<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * R-3: sirve el archivo CSV generado en cola por ProcesarExportacionTabla.
 * El token es un UUID; el archivo vive en storage/app/exports/{token}.csv.
 */
class ExportacionController extends Controller
{
    public function descargar(string $token)
    {
        $ruta = "exports/{$token}.csv";

        abort_unless(Storage::disk('local')->exists($ruta), 404, 'Exportación no encontrada o ya expirada.');

        // Sanitiza el token para evitar path traversal (solo alfanuméricos y -).
        if (! preg_match('/^[a-zA-Z0-9-]+$/', $token)) {
            abort(404);
        }

        $contenido = Storage::disk('local')->get($ruta);
        $nombre = 'exportacion-'.$token.'.csv';

        // Se conserva el archivo para posibles re-descargas dentro de la sesión.
        return response($contenido, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ]);
    }
}
