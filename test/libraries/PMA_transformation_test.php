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

/**
 * tests for transformation wrappers
 *
 * @package PhpMyAdmin-test
 */
class PMA_Transformation_Test extends PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $this->assertEquals(
            array('option1 ', ' option2 '),
            PMA_transformation_getOptions("option1 , option2 ")
        );
    }

    public function testQuoted()
    {
        $this->assertEquals(
            array('option1', ' option2'),
            PMA_transformation_getOptions("'option1' ,' option2' ")
        );
    }

    public function testComma()
    {
        $this->assertEquals(
            array('2,3', ' ,, option ,,'),
            PMA_transformation_getOptions("'2,3' ,' ,, option ,,' ")
        );
    }

    public function testEmptyOptions()
    {
        $this->assertEquals(
            array('', '', ''),
            PMA_transformation_getOptions("'',,")
        );
    }

    public function testEmpty()
    {
        $this->assertEquals(
            array(),
            PMA_transformation_getOptions('')
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
}
?>
