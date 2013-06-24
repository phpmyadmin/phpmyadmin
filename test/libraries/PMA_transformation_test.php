<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for transformation wrappers
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/transformations.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/Theme.class.php';

/**
 * tests for transformation wrappers
 *
 * @package PhpMyAdmin-test
 */
class PMA_Transformation_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Set up global environment.
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'db';
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['cfg'] = array(
            'MySQLManualType' => 'none',
            'ServerDefault' => 1,
            'ActionLinksMode' => 'icons',
        );
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['designer_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = 'column_info';
        $GLOBALS['cfg']['DBG']['sql'] = false;
        // need to clear relation test cache
        unset($_SESSION['relation']);
    }

    /**
     * Test for parsing options.
     *
     * @param string $input    String to parse
     * @param array  $expected Expected result
     *
     * @return void
     *
     * @dataProvider getOptionsData
     */
    public function testGetOptions($input, $expected)
    {
        $this->assertEquals(
            $expected,
            PMA_transformation_getOptions($input)
        );
    }

    /**
     * Data provided for parsing options
     *
     * @return array with test data
     */
    public function getOptionsData()
    {
        return array(
            array("option1 , option2 ", array('option1 ', ' option2 ')),
            array("'option1' ,' option2' ", array('option1', ' option2')),
            array("'2,3' ,' ,, option ,,' ", array('2,3', ' ,, option ,,')),
            array("'',,", array('', '', '')),
            array('', array()),
        );
    }

    /**
     * Test for getting available types.
     *
     * @return void
     */
    public function testGetTypes()
    {
        $this->assertEquals(
            array (
                'mimetype' => array (
                    'Application/Octetstream' => 'Application/Octetstream',
                    'Image/JPEG' => 'Image/JPEG',
                    'Image/PNG' => 'Image/PNG',
                    'Text/Plain' => 'Text/Plain',
                ),
                'transformation' => array (
                    0 => 'Application/Octetstream: Download',
                    1 => 'Application/Octetstream: Hex',
                    2 => 'Image/JPEG: Inline',
                    3 => 'Image/JPEG: Link',
                    4 => 'Image/PNG: Inline',
                    5 => 'Text/Plain: Append',
                    6 => 'Text/Plain: Dateformat',
                    7 => 'Text/Plain: External',
                    8 => 'Text/Plain: Formatted',
                    9 => 'Text/Plain: Imagelink',
                    10 => 'Text/Plain: Link',
                    11 => 'Text/Plain: Longtoipv4',
                    12 => 'Text/Plain: Sql',
                    13 => 'Text/Plain: Substring',
                    ),
                'transformation_file' => array (
                    0 => 'Application_Octetstream_Download.class.php',
                    1 => 'Application_Octetstream_Hex.class.php',
                    2 => 'Image_JPEG_Inline.class.php',
                    3 => 'Image_JPEG_Link.class.php',
                    4 => 'Image_PNG_Inline.class.php',
                    5 => 'Text_Plain_Append.class.php',
                    6 => 'Text_Plain_Dateformat.class.php',
                    7 => 'Text_Plain_External.class.php',
                    8 => 'Text_Plain_Formatted.class.php',
                    9 => 'Text_Plain_Imagelink.class.php',
                    10 => 'Text_Plain_Link.class.php',
                    11 => 'Text_Plain_Longtoipv4.class.php',
                    12 => 'Text_Plain_Sql.class.php',
                    13 => 'Text_Plain_Substring.class.php',
                ),
            ),
            PMA_getAvailableMIMEtypes()
        );
    }

    /**
     * Tests getting mime types for table
     *
     * @return void
     */
    public function testGetMime()
    {
        $this->assertEquals(
            array('o' => array(
                'column_name' => 'o',
                'mimetype' => 'Text/plain',
                'transformation' => 'Sql',
            )),
            PMA_getMIME('pma_test', 'table1')
        );
    }
}
?>
