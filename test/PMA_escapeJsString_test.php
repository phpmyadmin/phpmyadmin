<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
/**
 * Test for javascript escaping.
 *
 * @author Michal Čihař <michal@cihar.com>
 * @package phpMyAdmin-test
 * @version $Id$
 */

/**
 * Tests core.
 */
require_once 'PHPUnit/Framework.php';

/**
 * Include to test.
 */
require_once './libraries/js_escape.lib.php';

/**
 * Test java script escaping.
 *
 */
class PMA_escapeJsString_test extends PHPUnit_Framework_TestCase
{
    public function testEscape_1()
    {
        $this->assertEquals('\\\';', PMA_escapeJsString('\';'));
    }

    public function testEscape_2()
    {
        $this->assertEquals('\r\n\\\'<scrIpt></\' + \'script>', PMA_escapeJsString("\r\n'<scrIpt></sCRIPT>"));
    }

    public function testEscape_3()
    {
        $this->assertEquals('\\\';[XSS]', PMA_escapeJsString('\';[XSS]'));
    }

    public function testEscape_4()
    {
        $this->assertEquals('</\' + \'script></head><body>[HTML]', PMA_escapeJsString('</SCRIPT></head><body>[HTML]'));
    }

    public function testEscape_5()
    {
        $this->assertEquals('"\\\'\\\\\\\'"', PMA_escapeJsString('"\'\\\'"'));
    }

    public function testEscape_6()
    {
        $this->assertEquals("\\\\\'\'\'\'\'\'\'\'\'\'\'\'\\\\", PMA_escapeJsString("\\''''''''''''\\"));
    }

}
?>
