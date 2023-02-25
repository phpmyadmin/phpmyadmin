<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Pdf;

/** @covers \PhpMyAdmin\Pdf */
class PdfTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setGlobalConfig();
    }

    /**
     * Test for Pdf::getPDFData
     *
     * @group large
     */
    public function testBasic(): void
    {
        $arr = new Pdf();
        $this->assertStringContainsString('PDF', $arr->getPDFData());
    }

    /**
     * Test for Pdf::getPDFData
     *
     * @group large
     */
    public function testAlias(): void
    {
        $arr = new Pdf();
        $arr->setAlias('{00}', '32');
        $this->assertStringContainsString('PDF', $arr->getPDFData());
    }

    /**
     * Test for Pdf::getPDFData
     *
     * @group large
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
        $this->assertStringContainsString('PDF', $pdf->getPDFData());
    }
}
