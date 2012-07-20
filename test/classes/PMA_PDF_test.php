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
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/PDF.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/core.lib.php';
require_once 'libraries/Config.class.php';

class PMA_PDF_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
    }
    /**
     * @group large
     */
    public function testBasic()
    {
        $arr = new PMA_PDF();
        $this->assertContains('PDF', $arr->getPDFData());
    }

    /**
     * @group large
     */
    public function testAlias()
    {
        $arr = new PMA_PDF();
        $arr->SetAlias('{00}', '32');
        $this->assertContains('PDF', $arr->getPDFData());
    }
}
?>
