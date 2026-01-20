<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Pdf;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;

#[CoversClass(Pdf::class)]
#[Large]
class PdfTest extends AbstractTestCase
{
    /**
     * Test for Pdf::getPDFData
     */
    public function testBasic(): void
    {
        $arr = new Pdf();
        self::assertStringContainsString('PDF', $arr->getPDFData());
    }

    /**
     * Test for Pdf::getPDFData
     */
    public function testAlias(): void
    {
        $arr = new Pdf();
        $arr->setAlias('{00}', '32');
        self::assertStringContainsString('PDF', $arr->getPDFData());
    }

    /**
     * Test for Pdf::getPDFData
     */
    public function testDocument(): void
    {
        $pdf = new Pdf();
        $pdf->setTitle('Title');
        $pdf->Open();
        $pdf->setAutoPageBreak(true);
        $pdf->AddPage();
        $pdf->setFont(Pdf::PMA_PDF_FONT, 'B', 14);
        $pdf->Cell(0, 6, 'Cell', 'B', 1, 'C');
        $pdf->Ln();
        $pdf->AddPage();
        $pdf->Bookmark('Bookmark');
        $pdf->setMargins(0, 0);
        $pdf->setDrawColor(200, 200, 200);
        $pdf->Line(0, 0, 100, 100);
        self::assertStringContainsString('PDF', $pdf->getPDFData());
    }
}
