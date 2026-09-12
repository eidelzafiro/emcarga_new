<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Reportes del grupo DOCUMENTOS (cartas de porte y hojas de ruta): generación
 * de los 13 reportes legacy (layout FPDF idéntico al legacy).
 */
class DocumentosReportesTest extends TestCase
{
    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('SUPERADMIN');
        $user->password_temporal = false;
        $user->save();

        return $user;
    }

    #[DataProvider('reportes')]
    public function test_generar_documento(int $id, array $filtros): void
    {
        $resp = $this->actingAs($this->admin())
            ->get(route('reportes.documentos.generar', $id) . '?' . http_build_query($filtros));

        $resp->assertOk();
        $this->assertSame('application/pdf', $resp->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $resp->getContent());
    }

    public static function reportes(): array
    {
        return [
            'cp canceladas'        => [3, ['mes' => '2026-06']],
            'cp consecutivo'       => [4, ['consecutivo_desde' => 56736, 'consecutivo_hasta' => 56740]],
            'cp control estado'    => [5, ['mes' => '2026-06']],
            'cp pd emision'        => [6, ['fecha' => '2026-06-30']],
            'cp pd recepcion'      => [7, ['fecha' => '2026-05-31']],
            'cp registro res 213'  => [157, ['mes' => '2026-06']],
            'hr canceladas'        => [8, ['mes' => '2026-04']],
            'hr consecutivo'       => [9, ['consecutivo_desde' => 25300, 'consecutivo_hasta' => 25310]],
            'hr control estado'    => [10, ['mes' => '2026-06']],
            'hr pd cierre'         => [11, ['fecha' => '2026-05-31']],
            'hr pd emision'        => [12, ['fecha' => '2026-06-30']],
            'hr registro res 184'  => [158, ['mes' => '2026-06']],
            'hr analisis'          => [1014, ['mes' => '2026-06']],
        ];
    }
}
