<?php
/**
 * tests for transformation wrappers
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Transformations;

/**
 * tests for transformation wrappers
 */
class TransformationsTest extends AbstractTestCase
{
    /** @var Transformations */
    private $transformations;

    /**
     * Set up global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'db';
        $GLOBALS['cfg'] = [
            'ServerDefault' => 1,
            'ActionLinksMode' => 'icons',
        ];
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

        $this->transformations = new Transformations();
    }

    /**
     * Test for parsing options.
     *
     * @param string $input    String to parse
     * @param array  $expected Expected result
     *
     * @dataProvider getOptionsData
     */
    public function testGetOptions(string $input, array $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->transformations->getOptions($input)
        );
    }

    /**
     * Data provided for parsing options
     */
    public function getOptionsData(): array
    {
        return [
            [
                'option1 , option2 ',
                [
                    'option1 ',
                    ' option2 ',
                ],
            ],
            [
                "'option1' ,' option2' ",
                [
                    'option1',
                    ' option2',
                ],
            ],
            [
                "'2,3' ,' ,, option ,,' ",
                [
                    '2,3',
                    ' ,, option ,,',
                ],
            ],
            [
                "'',,",
                [
                    '',
                    '',
                    '',
                ],
            ],
            [
                '',
                [],
            ],
        ];
    }

    /**
     * Test for getting available types.
     */
    public function testGetTypes(): void
    {
        $this->assertEquals(
            [
                'mimetype' =>  [
                    'Application/Octetstream' => 'Application/Octetstream',
                    'Image/JPEG' => 'Image/JPEG',
                    'Image/PNG' => 'Image/PNG',
                    'Text/Plain' => 'Text/Plain',
                    'Text/Octetstream' => 'Text/Octetstream',
                ],
                'transformation' =>  [
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
                ],
                'transformation_file' =>  [
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
                ],
                'input_transformation' => [
                    'Image/JPEG: Upload',
                    'Text/Plain: FileUpload',
                    'Text/Plain: Iptobinary',
                    'Text/Plain: Iptolong',
                    'Text/Plain: JsonEditor',
                    'Text/Plain: RegexValidation',
                    'Text/Plain: SqlEditor',
                    'Text/Plain: XmlEditor',
                    'Text/Plain: Link',
                    'Text/Plain: Longtoipv4',
                    'Text/Plain: PreApPend',
                    'Text/Plain: Substring',
                ],
                'input_transformation_file' => [
                    'Input/Image_JPEG_Upload.php',
                    'Input/Text_Plain_FileUpload.php',
                    'Input/Text_Plain_Iptobinary.php',
                    'Input/Text_Plain_Iptolong.php',
                    'Input/Text_Plain_JsonEditor.php',
                    'Input/Text_Plain_RegexValidation.php',
                    'Input/Text_Plain_SqlEditor.php',
                    'Input/Text_Plain_XmlEditor.php',
                    'Text_Plain_Link.php',
                    'Text_Plain_Longtoipv4.php',
                    'Text_Plain_PreApPend.php',
                    'Text_Plain_Substring.php',
                ],
            ],
            $this->transformations->getAvailableMimeTypes()
        );
    }

    /**
     * Tests getting mime types for table
     */
    public function testGetMime(): void
    {
        $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][$GLOBALS['server']]['mimework'] = true;
        $_SESSION['relation'][$GLOBALS['server']]['db'] = 'pmadb';
        $_SESSION['relation'][$GLOBALS['server']]['column_info'] = 'column_info';
        $_SESSION['relation'][$GLOBALS['server']]['trackingwork'] = false;
        $this->assertEquals(
            [
                'o' => [
                    'column_name' => 'o',
                    'mimetype' => 'Text/plain',
                    'transformation' => 'Sql',
                    'transformation_options' => '',
                    'input_transformation' => 'regex',
                    'input_transformation_options' => '/pma/i',
                ],
                'col' => [
                    'column_name' => 'col',
                    'mimetype' => 'T',
                    'transformation' => 'O/P',
                    'transformation_options' => '',
                    'input_transformation' => 'i/p',
                    'input_transformation_options' => '',
                ],
            ],
            $this->transformations->getMime('pma_test', 'table1')
        );
    }

    /**
     * Test for clear
     */
    public function testClear(): void
    {
        // Mock dbi
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue(true));
        $GLOBALS['dbi'] = $dbi;

        // Case 1 : no configuration storage
        $actual = $this->transformations->clear('db');
        $this->assertFalse(
            $actual
        );

        $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][$GLOBALS['server']]['column_info'] = 'column_info';
        $_SESSION['relation'][$GLOBALS['server']]['db'] = 'pmadb';

        // Case 2 : database delete
        $actual = $this->transformations->clear('db');
        $this->assertTrue(
            $actual
        );

        // Case 3 : table delete
        $actual = $this->transformations->clear('db', 'table');
        $this->assertTrue(
            $actual
        );

        // Case 4 : column delete
        $actual = $this->transformations->clear('db', 'table', 'col');
        $this->assertTrue(
            $actual
        );
    }

    /**
     * @param string $value    value
     * @param string $expected expected result
     *
     * @dataProvider fixupData
     */
    public function testFixup(string $value, string $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->transformations->fixUpMime($value)
        );
    }

    public function fixupData(): array
    {
        return [
            [
                'text_plain_bool2text.php',
                'Text_Plain_Bool2Text.php',
            ],
            [
                'application_octetstream_download.php',
                'Application_Octetstream_Download.php',
            ],
            [
                'text_plain_json.php',
                'Text_Plain_Json.php',
            ],
            [
                'image_jpeg_link.php',
                'Image_JPEG_Link.php',
            ],
            [
                'text_plain_dateformat.php',
                'Text_Plain_Dateformat.php',
            ],
        ];
    }

    /**
     * Test for getDescription
     *
     * @param string $file                transformation file
     * @param string $expectedDescription expected description
     *
     * @dataProvider providerGetDescription
     */
    public function testGetDescription(string $file, string $expectedDescription): void
    {
        $this->assertEquals(
            $expectedDescription,
            $this->transformations->getDescription($file)
        );
    }

    public function providerGetDescription(): array
    {
        return [
            [
                '../../../../test',
                '',
            ],
            [
                'Input/Text_Plain_SqlEditor',
                'Syntax highlighted CodeMirror editor for SQL.',
            ],
            [
                'Output/Text_Plain_Sql',
                'Formats text as SQL query with syntax highlighting.',
            ],
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
    public function testGetName(string $file, string $expectedName): void
    {
        $this->assertEquals(
            $expectedName,
            $this->transformations->getName($file)
        );
    }

    public function providerGetName(): array
    {
        return [
            [
                '../../../../test',
                '',
            ],
            [
                'Input/Text_Plain_SqlEditor',
                'SQL',
            ],
            [
                'Output/Text_Plain_Sql',
                'SQL',
            ],
        ];
    }
}
