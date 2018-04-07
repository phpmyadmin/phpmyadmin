<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for Pdf class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Pdf;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * tests for Pdf class
 *
 * @package PhpMyAdmin-test
 */
class PdfTest extends PmaTestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
    }

    /**
     * Test for Pdf::getPDFData
     *
     * @group large
     * @return void
     */
    public function testBasic()
    {
        $arr = new Pdf();
        $this->assertContains('PDF', $arr->getPDFData());
    }

    /**
     * Test for Pdf::getPDFData
     *
     * @group large
     * @return void
     */
    public function testAlias()
    {
        $arr = new Pdf();
        $arr->setAlias('{00}', '32');
        $this->assertContains('PDF', $arr->getPDFData());
    }

    /**
     * Test for Pdf::getPDFData
     *
     * @group large
     * @return void
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
        $this->assertContains('PDF', $pdf->getPDFData());
    }
}
