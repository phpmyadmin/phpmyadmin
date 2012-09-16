<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_Util::getBrowseUploadFileBlock from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_getBrowseUploadFileBlock_test extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $GLOBALS['is_upload'] = false;
    }

    /*
     * Data provider for test
     */
    public function dataProvider()
    {
        return array(
            array(10, __('B'), "10"),
            array(100, __('B'), "100"),
            array(1024, __('B'), "1,024"),
            array(102400, __('KiB'), "100"),
            array(10240000, __('MiB'), "10"),
            array(2147483648, __('MiB'), "2,048"),
            array(21474836480, __('GiB'), "20")
        );
    }

    /**
     * @dataProvider dataProvider
     * @return void
     */
    function testBrowseUploadFile($size, $unit, $res)
    {
        $this->assertEquals(
            PMA_Util::getBrowseUploadFileBlock($size),
            '<label for="input_import_file">' . __("Browse your computer:") . '</label>'
            . '<div id="upload_form_status" style="display: none;"></div>'
            . '<div id="upload_form_status_info" style="display: none;"></div>'
            . '<input type="file" name="import_file" id="input_import_file" />'
            . "(" . __('Max: '). $res . $unit .")" . "\n"
            . '<input type="hidden" name="MAX_FILE_SIZE" value="' .$size . '" />' . "\n"
        );        
    }
}
