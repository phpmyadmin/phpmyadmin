<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for javascript escaping.
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\Sanitize;


/**
 * Test for javascript escaping.
 *
 * @package PhpMyAdmin-test
 */
class PMA_EscapeJsString_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Sanitize::escapeJsString tests
     *
     * @param string $target expected output
     * @param string $source string to be escaped
     *
     * @return void
     * @dataProvider escapeDataProvider
     */
    public function testEscape($target, $source)
    {
        $this->assertEquals($target, Sanitize::escapeJsString($source));
    }

    /**
     * Data provider for testEscape
     *
     * @return array data for testEscape test case
     */
    public function escapeDataProvider()
    {
        return array(
            array('\\\';', '\';'),
            array('\r\n\\\'<scrIpt></\' + \'script>', "\r\n'<scrIpt></sCRIPT>"),
            array('\\\';[XSS]', '\';[XSS]'),
            array(
                '</\' + \'script></head><body>[HTML]',
                '</SCRIPT></head><body>[HTML]'
            ),
            array('\"\\\'\\\\\\\'\"', '"\'\\\'"'),
            array("\\\\\'\'\'\'\'\'\'\'\'\'\'\'\\\\", "\\''''''''''''\\")
        );
    }
}
