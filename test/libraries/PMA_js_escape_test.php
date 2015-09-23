<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for JS variable formatting
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/Util.class.php';

/**
 * tests for JS variable formatting
 *
 * @package PhpMyAdmin-test
 */
class PMA_JS_Escape_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_getJsValue
     *
     * @param string $key      Key
     * @param string $value    Value
     * @param string $expected Expected output
     *
     * @dataProvider variables
     *
     * @return void
     */
    public function testFormat($key, $value, $expected)
    {
        $this->assertEquals($expected, PMA_getJsValue($key, $value));
        $this->assertEquals('foo = 100', PMA_getJsValue('foo', '100', false));
        $array = array('1','2','3');
        $this->assertEquals(
            "foo = [\"1\",\"2\",\"3\",];\n",
            PMA_getJsValue('foo', $array)
        );
    }

    /**
     * Test for PMA_jsFormat
     *
     * @return void
     */
    public function testJsFormat()
    {
        $this->assertEquals("`foo`", PMA_jsFormat('foo'));
    }

    /**
     * Provider for testFormat
     *
     * @return array
     */
    public function variables()
    {
        return array(
            array('foo', true, "foo = true;\n"),
            array('foo', false, "foo = false;\n"),
            array('foo', 100, "foo = 100;\n"),
            array('foo', 0, "foo = 0;\n"),
            array('foo', 'text', "foo = \"text\";\n"),
            array('foo', 'quote"', "foo = \"quote\\\"\";\n"),
            array('foo', 'apostroph\'', "foo = \"apostroph\\'\";\n"),
        );
    }
}
