<?php

namespace App\Services\Reports\Fpdf;

use FPDF;

/**
 * Clase base para reportes FPDF replicando el comportamiento del legacy.
 * Maneja: logo dinámico, header configurable, titulos multicolumna, firmas.
 */
abstract class FpdfReportBase extends FPDF
{
    protected int $entidadId;
    protected string $mes;
    protected string $ano;
    protected int $logoWidth = 35;
    protected int $logoHeight = 20;
    protected int $titlePosX = 10;
    protected int $titlePosY = 5;

    public function __construct(int $entidadId, string $mes, string $ano)
    {
        parent::__construct('L', 'mm', 'Letter');
        $this->entidadId = $entidadId;
        $this->mes = $mes;
        $this->ano = $ano;
        $this->SetAutoPageBreak(false);
        $this->AliasNbPages();
    }

    /**
     * Header base: logo dinámico según idsistema + título + info entidad.
     * Replica inicio() del legacy (Reportesh.php:63).
     */
    public function inicio(string $titulo, int $posX = 10, int $posY = 5): void
    {
        $this->AddPage();
        $this->SetAutoPageBreak(false);

        // Logo dinámico según idsistema
        $logoFile = $this->getLogoFile();
        if ($logoFile && file_exists($logoFile)) {
            $this->Image($logoFile, 10, 5, $this->logoWidth, $this->logoHeight);
        }

        // Título principal
        $this->SetXY($posX, $posY);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 6, 'GESTION INTEGRAL DEL PARQUE AUTOMOTOR', 0, 1, 'C');

        // Subtítulo del reporte
        $this->SetXY($posX, $posY + 6);
        $this->SetFont('Arial', 'B', 13);
        $this->Cell(0, 6, $titulo, 0, 1, 'C');

        // Info: mes, año, entidad
        $this->SetXY($posX, $posY + 12);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'MES: ' . $this->mes . '  AÑO: ' . $this->ano, 0, 1, 'C');
    }

    /**
     * Títulos multicolumna. Replica titulos() del legacy (Reportesh.php:155).
     * Cada columna es un array con: titulo, ancho, direccion, bordes, letra.
     */
    public function titulos(array $campos2, array $campos, array $campos1, int $posY = 30): void
    {
        $posX = 10;
        $rowHeight = 6;

        // Fila 3 (campos2) - fondo
        $this->SetXY($posX, $posY);
        foreach ($campos2 as $campo) {
            $this->setColumnFont($campo, '8');
            $bordes = $campo['bordes'] ?? '';
            $fill = empty($bordes) ? 0 : 1;
            $this->Cell($campo['ancho'], $rowHeight, $campo['titulo'], 1, 0, $campo['direccion'], $fill);
        }
        $this->Ln($rowHeight);

        // Fila 1 (campos) - header
        $this->SetXY($posX, $posY + $rowHeight);
        foreach ($campos as $campo) {
            $this->setColumnFont($campo, '9');
            $this->Cell($campo['ancho'], $rowHeight, $campo['titulo'], 1, 0, $campo['direccion'], 1);
        }
        $this->Ln($rowHeight);

        // Fila 2 (campos1) - sub-header
        $this->SetXY($posX, $posY + $rowHeight * 2);
        foreach ($campos1 as $campo) {
            $this->setColumnFont($campo, '9');
            $this->Cell($campo['ancho'], $rowHeight, $campo['titulo'], 1, 0, $campo['direccion'], 1);
        }
        $this->Ln($rowHeight);
    }

    /**
     * Firmas: CONF / APROB / ACUSE RECIBO + page X/Y + fecha emisión.
     * Replica pdf_salario_firmas() del legacy (Reportesh.php:306).
     */
    public function firmas(int $posY = 170): void
    {
        $this->SetXY(10, $posY);
        $this->SetFont('Arial', '', 10);

        // Firmas
        $this->Cell(60, 6, '________________________', 0, 0, 'C');
        $this->Cell(60, 6, '________________________', 0, 0, 'C');
        $this->Cell(60, 6, '________________________', 0, 1, 'C');

        $this->Cell(60, 6, 'CONF', 0, 0, 'C');
        $this->Cell(60, 6, 'APROB', 0, 0, 'C');
        $this->Cell(60, 6, 'ACUSE RECIBO', 0, 1, 'C');

        // Page X/Y
        $this->SetXY(10, $posY + 12);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 6, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');

        // Fecha emisión
        $this->SetXY(10, $posY + 18);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 6, 'Fecha emisión: ' . date('d/m/Y'), 0, 0, 'C');
    }

    /**
     * Obtener archivo de logo según idsistema de la entidad.
     */
    protected function getLogoFile(): ?string
    {
        $entidad = \App\Models\Entidad::find($this->entidadId);
        if (!$entidad) return null;

        $idsistema = $entidad->idsistema ?? 0;
        $logos = [
            0 => 'enoc.png',
            1 => 'udecam.png',
            2 => 'etmicons.png',
            3 => 'etrac.png',
            4 => 'etag.png',
        ];

        $logoName = $logos[$idsistema] ?? $logos[0];
        $path = public_path('assets/images/' . $logoName);

        return file_exists($path) ? $path : null;
    }

    /**
     * Configurar fuente de columna según ancho.
     */
    protected function setColumnFont(array $campo, string $defaultSize = '9'): void
    {
        $size = $campo['letra'] ?? $defaultSize;
        $this->SetFont('Arial', '', (int) $size);
    }

    /**
     * Formatear número con decimales (replica cambiarVariable del legacy).
     */
    protected function fmt($value, int $decimals = 2): string
    {
        if ($value === '' || $value === null) return '';
        return number_format((float) $value, $decimals, '.', '');
    }
}
