<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_PDF class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/PDF.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/core.lib.php';
require_once 'libraries/Config.class.php';

/**
 * tests for PMA_PDF class
 *
 * @package PhpMyAdmin-test
 */
class PMA_PDF_Test extends PHPUnit_Framework_TestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
    }

    /**
     * Test for PMA_PDF::getPDFData
     *
     * @group large
     * @return void
     */
    public function testBasic()
    {
        $arr = new PMA_PDF();
        $this->assertContains('PDF', $arr->getPDFData());
    }

    /**
     * Test for PMA_PDF::getPDFData
     *
     * @group large
     * @return void
     */
    public function testAlias()
    {
        $arr = new PMA_PDF();
        $arr->SetAlias('{00}', '32');
        $this->assertContains('PDF', $arr->getPDFData());
    }

    /**
     * Test for PMA_PDF::getPDFData
     *
     * @group large
     * @return void
     */
    public function testDocument()
    {
        $pdf = new PMA_PDF();
        $pdf->SetTitle('Title');
        $pdf->Open();
        $pdf->SetAutoPageBreak('auto');
        $pdf->Addpage();
        $pdf->SetFont(PMA_PDF_FONT, 'B', 14);
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
?>
