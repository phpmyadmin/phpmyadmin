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
require_once 'libraries/common.lib.php';
require_once 'libraries/PDF.class.php';
require_once 'libraries/php-gettext/gettext.inc';

if (!defined('PMA_VERSION')) {
    define('PMA_VERSION', 'TEST');
}

class PMA_PDF_test extends PHPUnit_Framework_TestCase
{
    public function testBasic()
    {
        $arr = new PMA_PDF();
        $this->assertContains('PDF', $arr->getPDFData());
    }

    public function testAlias()
    {
        $arr = new PMA_PDF();
        $arr->SetAlias('{00}', '32');
        $this->assertContains('PDF', $arr->getPDFData());
    }
}
?>
