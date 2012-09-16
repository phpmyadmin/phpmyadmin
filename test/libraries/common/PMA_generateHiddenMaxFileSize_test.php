<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::generateHiddenMaxFileSize from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

class PMA_generateHiddenMaxFileSize_test extends PHPUnit_Framework_TestCase
{

    /*
     * Data provider for test
     */
    public function dataProvider()
    {
        return array(
            array(10),
            array("100"),
            array(1024),
            array("1024Mb"),
            array(2147483648),
            array("some_string")
        );
    }

    /**
     * @dataProvider dataProvider
     * @return void
     */
    function test_generateHiddenMaxFileSize($size)
    {
        $this->assertEquals(
            PMA_Util::generateHiddenMaxFileSize($size),
            '<input type="hidden" name="MAX_FILE_SIZE" value="' . $size . '" />'
        );
    }
}
