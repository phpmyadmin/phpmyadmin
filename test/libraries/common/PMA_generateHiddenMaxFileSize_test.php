<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_generateHiddenMaxFileSize from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_generateHiddenMaxFileSize_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_generateHiddenMaxFileSize_test extends PHPUnit_Framework_TestCase{

    /*
     * Data provider for test
     */
    public function dataProvider() {
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
    function test_generateHiddenMaxFileSize($size){
        $this->assertEquals(PMA_generateHiddenMaxFileSize($size),
                            '<input type="hidden" name="MAX_FILE_SIZE" value="' .$size . '" />');
    }
}
