<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PDF class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\PDF;

require_once 'test/PMATestCase.php';

/**
 * tests for PDF class
 *
 * @package PhpMyAdmin-test
 */
class PDFTest extends PMATestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config();
        $GLOBALS['PMA_Config']->enableBc();
    }

    /**
     * Test for PDF::getPDFData
     *
     * @group large
     * @return void
     */
    public function testBasic()
    {
        $arr = new PDF();
        $this->assertContains('PDF', $arr->getPDFData());
    }

    /**
     * Test for PDF::getPDFData
     *
     * @group large
     * @return void
     */
    public function testAlias()
    {
        $arr = new PDF();
        $arr->SetAlias('{00}', '32');
        $this->assertContains('PDF', $arr->getPDFData());
    }

    /**
     * Test for PDF::getPDFData
     *
     * @group large
     * @return void
     */
    public function testDocument()
    {
        $pdf = new PDF();
        $pdf->SetTitle('Title');
        $pdf->Open();
        $pdf->SetAutoPageBreak('auto');
        $pdf->Addpage();
        $pdf->SetFont(PDF::PMA_PDF_FONT, 'B', 14);
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
