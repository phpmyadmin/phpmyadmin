<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\export\ExportCsv class
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\plugins\export\ExportCsv;

require_once 'libraries/export.lib.php';
require_once 'libraries/config.default.php';
require_once 'export.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\export\ExportCsv class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportCsvTest extends PMATestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['server'] = 0;
        $this->object = new ExportCsv();
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCsv::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportCsv', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PMA\libraries\plugins\export\ExportCsv', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PMA\libraries\properties\plugins\ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'CSV',
            $properties->getText()
        );

        $this->assertEquals(
            'csv',
            $properties->getExtension()
        );

        $this->assertEquals(
            'text/comma-separated-values',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'separator',
            $property->getName()
        );

        $this->assertEquals(
            'Columns separated with:',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'enclosed',
            $property->getName()
        );

        $this->assertEquals(
            'Columns enclosed with:',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'escaped',
            $property->getName()
        );

        $this->assertEquals(
            'Columns escaped with:',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'terminated',
            $property->getName()
        );

        $this->assertEquals(
            'Lines terminated with:',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\TextPropertyItem',
            $property
        );

        $this->assertEquals(
            'null',
            $property->getName()
        );

        $this->assertEquals(
            'Replace NULL with:',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'removeCRLF',
            $property->getName()
        );

        $this->assertEquals(
            'Remove carriage return/line feed characters within columns',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\BoolPropertyItem',
            $property
        );

        $this->assertEquals(
            'columns',
            $property->getName()
        );

        $this->assertEquals(
            'Put columns names in the first row',
            $property->getText()
        );

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PMA\libraries\properties\options\items\HiddenPropertyItem',
            $property
        );

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );

    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCsv::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        // case 1

        $GLOBALS['what'] = 'excel';
        $GLOBALS['excel_edition'] = 'win';
        $GLOBALS['excel_columns'] = true;

        $this->assertTrue(
            $this->object->exportHeader()
        );

        $this->assertEquals(
            "\015\012",
            $GLOBALS['csv_terminated']
        );

        $this->assertEquals(
            ";",
            $GLOBALS['csv_separator']
        );

        $this->assertEquals(
            '"',
            $GLOBALS['csv_enclosed']
        );

        $this->assertEquals(
            '"',
            $GLOBALS['csv_escaped']
        );

        $this->assertEquals(
            'yes',
            $GLOBALS['csv_columns']
        );

        // case 2

        $GLOBALS['excel_edition'] = 'mac_excel2003';
        unset($GLOBALS['excel_columns']);
        $GLOBALS['csv_columns'] = 'no';

        $this->assertTrue(
            $this->object->exportHeader()
        );

        $this->assertEquals(
            "\015\012",
            $GLOBALS['csv_terminated']
        );

        $this->assertEquals(
            ";",
            $GLOBALS['csv_separator']
        );

        $this->assertEquals(
            '"',
            $GLOBALS['csv_enclosed']
        );

        $this->assertEquals(
            '"',
            $GLOBALS['csv_escaped']
        );

        $this->assertEquals(
            'no',
            $GLOBALS['csv_columns']
        );

        // case 3

        $GLOBALS['excel_edition'] = 'mac_excel2008';

        $this->assertTrue(
            $this->object->exportHeader()
        );

        $this->assertEquals(
            "\015\012",
            $GLOBALS['csv_terminated']
        );

        $this->assertEquals(
            ",",
            $GLOBALS['csv_separator']
        );

        $this->assertEquals(
            '"',
            $GLOBALS['csv_enclosed']
        );

        $this->assertEquals(
            '"',
            $GLOBALS['csv_escaped']
        );

        $this->assertEquals(
            'no',
            $GLOBALS['csv_columns']
        );

        // case 4

        $GLOBALS['excel_edition'] = 'testBlank';
        $GLOBALS['csv_separator'] = '#';

        $this->assertTrue(
            $this->object->exportHeader()
        );

        $this->assertEquals(
            '#',
            $GLOBALS['csv_separator']
        );

        // case 5

        $GLOBALS['what'] = 'notExcel';
        $GLOBALS['crlf'] = "\n";
        $GLOBALS['csv_terminated'] = '';
        $GLOBALS['csv_separator'] = 'a\\t';

        $this->assertTrue(
            $this->object->exportHeader()
        );

        $this->assertEquals(
            $GLOBALS['csv_terminated'],
            "\n"
        );

        $this->assertEquals(
            $GLOBALS['csv_separator'],
            "a\011"
        );
        // case 6

        $GLOBALS['csv_terminated'] = 'AUTO';

        $this->assertTrue(
            $this->object->exportHeader()
        );

        $this->assertEquals(
            $GLOBALS['csv_terminated'],
            "\n"
        );

        // case 7

        $GLOBALS['csv_terminated'] = 'a\\rb\\nc\\t';
        $GLOBALS['csv_separator'] = 'a\\t';

        $this->assertTrue(
            $this->object->exportHeader()
        );

        $this->assertEquals(
            $GLOBALS['csv_terminated'],
            "a\015b\012c\011"
        );

        $this->assertEquals(
            $GLOBALS['csv_separator'],
            "a\011"
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCsv::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCsv::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCsv::exportDBFooter
     *
     * @return void
     */
    public function testExportDBFooter()
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB')
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCsv::exportDBCreate
     *
     * @return void
     */
    public function testExportDBCreate()
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database')
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportCsv::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        // case 1
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['csv_columns'] = 'yes';
        $GLOBALS['csv_terminated'] = ';';

        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = true;
        $GLOBALS['file_handle'] = null;

        ob_start();
        $this->assertFalse(
            $this->object->exportData(
                'testDB', 'testTable', "\n", 'example.com', 'test'
            )
        );
        $result = ob_get_clean();

        // case 2
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('test', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(1));

        $dbi->expects($this->once())
            ->method('fieldName')
            ->with(true, 0)
            ->will($this->returnValue("foo'\\bar"));

        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array(null, 'b', 'c', false, 'e', 'f')));

        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['what'] = 'UT';
        $GLOBALS['UT_null'] = 'customNull';
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'testDB', 'testTable', "\n", 'example.com', 'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            "foo'ba;customNull;",
            $result
        );

        // case 3

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('test', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(1));

        $dbi->expects($this->once())
            ->method('fieldName')
            ->with(true, 0)
            ->will($this->returnValue("foo\"\\bar"));

        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array(1 => 'a')));

        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['csv_enclosed'] = '"';

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'testDB', 'testTable', "\n", 'example.com', 'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            "\"foo\"bar;customNull;",
            $result
        );

        // case 4

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('test', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(1));

        $dbi->expects($this->once())
            ->method('fieldName')
            ->with(true, 0)
            ->will($this->returnValue("foo\"\\bar"));

        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array("test\015\012\n")));

        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['what'] = 'excel';
        $GLOBALS['excel_removeCRLF'] = true;
        $GLOBALS['csv_escaped'] = '"';

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'testDB', 'testTable', "\n", 'example.com', 'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            "\"foo\"\"bar;\"test\";",
            $result
        );

        // case 5

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('test', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(1));

        $dbi->expects($this->once())
            ->method('fieldName')
            ->with(true, 0)
            ->will($this->returnValue("foo\"\\bar"));

        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array("test\015\n")));

        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['csv_enclosed'] = '"';
        unset($GLOBALS['excel_removeCRLF']);
        $GLOBALS['csv_escaped'] = ';';

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'testDB', 'testTable', "\n", 'example.com', 'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            "\"foo;\"bar;\"test\n\";",
            $result
        );

        // case 6

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('test', null, PMA\libraries\DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(2));

        $dbi->expects($this->any())
            ->method('fieldName')
            ->will($this->returnValue("foo\"\\bar"));

        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(array("test\015\n", "test\n")));

        $dbi->expects($this->at(5))
            ->method('fetchRow')
            ->with(true)
            ->will($this->returnValue(null));
        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['csv_escaped'] = ';';
        $GLOBALS['csv_escaped'] = '#';

        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'testDB', 'testTable', "\n", 'example.com', 'test'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            "\"foo#\"bar\"\"foo#\"bar;\"test\n" .
            "\"\"test\n" .
            "\";",
            $result
        );
    }
}
