<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for transformation wrappers
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Theme;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\TestCase;

/**
 * tests for transformation wrappers
 *
 * @package PhpMyAdmin-test
 */
class TransformationsTest extends TestCase
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
        $GLOBALS['cfg'] = array(
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
            Transformations::getOptions($input)
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
                    'Text/Octetstream' => 'Text/Octetstream'
                ),
                'transformation' => array (
                    0 => 'Application/Octetstream: Download',
                    1 => 'Application/Octetstream: Hex',
                    2 => 'Image/JPEG: Inline',
                    3 => 'Image/JPEG: Link',
                    4 => 'Image/PNG: Inline',
                    5 => 'Text/Octetstream: Sql',
                    6 => 'Text/Plain: Binarytoip',
                    7 => 'Text/Plain: Bool2Text',
                    8 => 'Text/Plain: Dateformat',
                    9 => 'Text/Plain: External',
                    10 => 'Text/Plain: Formatted',
                    11 => 'Text/Plain: Imagelink',
                    12 => 'Text/Plain: Json',
                    13 => 'Text/Plain: Sql',
                    14 => 'Text/Plain: Xml',
                    15 => 'Text/Plain: Link',
                    16 => 'Text/Plain: Longtoipv4',
                    17 => 'Text/Plain: PreApPend',
                    18 => 'Text/Plain: Substring',
                    ),
                'transformation_file' => array (
                    0 => 'Output/Application_Octetstream_Download.php',
                    1 => 'Output/Application_Octetstream_Hex.php',
                    2 => 'Output/Image_JPEG_Inline.php',
                    3 => 'Output/Image_JPEG_Link.php',
                    4 => 'Output/Image_PNG_Inline.php',
                    5 => 'Output/Text_Octetstream_Sql.php',
                    6 => 'Output/Text_Plain_Binarytoip.php',
                    7 => 'Output/Text_Plain_Bool2Text.php',
                    8 => 'Output/Text_Plain_Dateformat.php',
                    9 => 'Output/Text_Plain_External.php',
                    10 => 'Output/Text_Plain_Formatted.php',
                    11 => 'Output/Text_Plain_Imagelink.php',
                    12 => 'Output/Text_Plain_Json.php',
                    13 => 'Output/Text_Plain_Sql.php',
                    14 => 'Output/Text_Plain_Xml.php',
                    15 => 'Text_Plain_Link.php',
                    16 => 'Text_Plain_Longtoipv4.php',
                    17 => 'Text_Plain_PreApPend.php',
                    18 => 'Text_Plain_Substring.php',
                ),
                'input_transformation' => array(
                    'Image/JPEG: Upload',
                    'Text/Plain: FileUpload',
                    'Text/Plain: Iptobinary',
                    'Text/Plain: JsonEditor',
                    'Text/Plain: RegexValidation',
                    'Text/Plain: SqlEditor',
                    'Text/Plain: XmlEditor',
                    'Text/Plain: Link',
                    'Text/Plain: Longtoipv4',
                    'Text/Plain: PreApPend',
                    'Text/Plain: Substring',
                ),
                'input_transformation_file' => array(
                    'Input/Image_JPEG_Upload.php',
                    'Input/Text_Plain_FileUpload.php',
                    'Input/Text_Plain_Iptobinary.php',
                    'Input/Text_Plain_JsonEditor.php',
                    'Input/Text_Plain_RegexValidation.php',
                    'Input/Text_Plain_SqlEditor.php',
                    'Input/Text_Plain_XmlEditor.php',
                    'Text_Plain_Link.php',
                    'Text_Plain_Longtoipv4.php',
                    'Text_Plain_PreApPend.php',
                    'Text_Plain_Substring.php',
                ),
            ),
            Transformations::getAvailableMIMEtypes()
        );
    }

    /**
     * Tests getting mime types for table
     *
     * @return void
     */
    public function testGetMime()
    {
        $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][$GLOBALS['server']]['mimework'] = true;
        $_SESSION['relation'][$GLOBALS['server']]['db'] = "pmadb";
        $_SESSION['relation'][$GLOBALS['server']]['column_info'] = "column_info";
        $_SESSION['relation'][$GLOBALS['server']]['trackingwork'] = false;
        $this->assertEquals(
            array(
                'o' => array(
                    'column_name' => 'o',
                    'mimetype' => 'Text/plain',
                    'transformation' => 'Sql',
                    'transformation_options' => '',
                    'input_transformation' => 'regex',
                    'input_transformation_options' => '/pma/i',
                ),
                'col' => array(
                    'column_name' => 'col',
                    'mimetype' => 'T',
                    'transformation' => 'O/P',
                    'transformation_options' => '',
                    'input_transformation' => 'i/p',
                    'input_transformation_options' => '',
                ),
            ),
            Transformations::getMIME('pma_test', 'table1')
        );
    }

    /**
     * Test for Transformations::clear
     *
     * @return void
     */
    public function testClear()
    {
        // Mock dbi
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue(true));
        $GLOBALS['dbi'] = $dbi;

        // Case 1 : no configuration storage
        $actual = Transformations::clear('db');
        $this->assertEquals(
            false,
            $actual
        );

        $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][$GLOBALS['server']]['column_info'] = "column_info";
        $_SESSION['relation'][$GLOBALS['server']]['db'] = "pmadb";

        // Case 2 : database delete
        $actual = Transformations::clear('db');
        $this->assertEquals(
            true,
            $actual
        );

        // Case 3 : table delete
        $actual = Transformations::clear('db', 'table');
        $this->assertEquals(
            true,
            $actual
        );

        // Case 4 : column delete
        $actual = Transformations::clear('db', 'table', 'col');
        $this->assertEquals(
            true,
            $actual
        );
    }

    /**
     * @dataProvider fixupData
     */
    public function testFixup($value, $expected)
    {
        $this->assertEquals(
            $expected,
            Transformations::fixupMIME($value)
        );
    }

    public function fixupData()
    {
        return array(
            array(
                'text_plain_bool2text.php',
                'Text_Plain_Bool2Text.php'
            ),
            array(
                'application_octetstream_download.php',
                'Application_Octetstream_Download.php'
            ),
            array(
                'text_plain_json.php',
                'Text_Plain_Json.php'
            ),
            array(
                'image_jpeg_link.php',
                'Image_JPEG_Link.php'
            ),
            array(
                'text_plain_dateformat.php',
                'Text_Plain_Dateformat.php'
            ),
        );
    }

    /**
     * Test for getDescription
     *
     * @param string $file                transformation file
     * @param string $expectedDescription expected description
     *
     * @dataProvider providerGetDescription
     */
    public function testGetDescription($file, $expectedDescription)
    {
        $this->assertEquals($expectedDescription, Transformations::getDescription($file));
    }

    /**
     * @return array
     */
    public function providerGetDescription()
    {
        return [
            ['../../../../test', ''],
            ['Input/Text_Plain_SqlEditor', 'Syntax highlighted CodeMirror editor for SQL.'],
            ['Output/Text_Plain_Sql', 'Formats text as SQL query with syntax highlighting.']
        ];
    }

    /**
     * Test for getName
     *
     * @param string $file         transformation file
     * @param string $expectedName expected name
     *
     * @dataProvider providerGetName
     */
    public function testGetName($file, $expectedName)
    {
        $this->assertEquals($expectedName, Transformations::getName($file));
    }

    /**
     * @return array
     */
    public function providerGetName()
    {
        return [
            ['../../../../test', ''],
            ['Input/Text_Plain_SqlEditor', 'SQL'],
            ['Output/Text_Plain_Sql', 'SQL']
        ];
    }
}
