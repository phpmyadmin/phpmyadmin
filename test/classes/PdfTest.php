<?php
/**
 * tests for Pdf class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Pdf;

/**
 * tests for Pdf class
 */
class PdfTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
    }

    /**
     * Test for Pdf::getPDFData
     *
     * @return void
     *
     * @group large
     */
    public function testBasic()
    {
        $arr = new Pdf();
        $this->assertStringContainsString('PDF', $arr->getPDFData());
    }

    /**
     * Test for Pdf::getPDFData
     *
     * @return void
     *
     * @group large
     */
    public function testAlias()
    {
        $arr = new Pdf();
        $arr->setAlias('{00}', '32');
        $this->assertStringContainsString('PDF', $arr->getPDFData());
    }

    /**
     * Test for Pdf::getPDFData
     *
     * @return void
     *
     * @group large
     */
    public function testDocument()
    {
        $pdf = new Pdf();
        $pdf->SetTitle('Title');
        $pdf->Open();
        $pdf->SetAutoPageBreak('auto');
        $pdf->Addpage();
        $pdf->SetFont(Pdf::PMA_PDF_FONT, 'B', 14);
        $pdf->Cell(0, 6, 'Cell', 'B', 1, 'C');
        $pdf->Ln();
        $pdf->Addpage();
        $pdf->Bookmark('Bookmark');
        $pdf->SetMargins(0, 0);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->line(0, 0, 100, 100);
        $this->assertStringContainsString('PDF', $pdf->getPDFData());
    }
}
