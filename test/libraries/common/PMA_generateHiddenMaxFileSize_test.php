<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA\libraries\Util::generateHiddenMaxFileSize from Util.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */


/**
 ** Test for PMA\libraries\Util::generateHiddenMaxFileSize from Util.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_GenerateHiddenMaxFileSize_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Data provider for test
     *
     * @return array
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
     * Test for generateHiddenMaxFileSize
     *
     * @param int $size Size
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    function testGenerateHiddenMaxFileSize($size)
    {
        $this->assertEquals(
            PMA\libraries\Util::generateHiddenMaxFileSize($size),
            '<input type="hidden" name="MAX_FILE_SIZE" value="' . $size . '" />'
        );
    }
}
