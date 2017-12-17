<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Plugins\Export\ExportOds class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportOds;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

/**
 * tests for PhpMyAdmin\Plugins\Export\ExportOds class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportOdsTest extends PmaTestCase
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
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $this->object = new ExportOds();
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
     * Test for PhpMyAdmin\Plugins\Export\ExportOds::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PhpMyAdmin\Plugins\Export\ExportOds', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PhpMyAdmin\Plugins\Export\ExportOds', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Plugins\ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'OpenDocument Spreadsheet',
            $properties->getText()
        );

        $this->assertEquals(
            'ods',
            $properties->getExtension()
        );

        $this->assertEquals(
            'application/vnd.oasis.opendocument.spreadsheet',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
        );

        $this->assertTrue(
            $properties->getForceFile()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup',
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup',
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Options\Items\TextPropertyItem',
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
            'PhpMyAdmin\Properties\Options\Items\BoolPropertyItem',
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
            'PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem',
            $property
        );

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );

    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportOds::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        $this->assertArrayHasKey(
            'ods_buffer',
            $GLOBALS
        );

        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportOds::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $GLOBALS['ods_buffer'] = 'header';

        $this->expectOutputRegex('/^504b.*636f6e74656e742e786d6c/');
        $this->setOutputCallback('bin2hex');

        $this->assertTrue(
            $this->object->exportFooter()
        );

        $this->assertContains(
            'header',
            $GLOBALS['ods_buffer']
        );

        $this->assertContains(
            '</office:spreadsheet>',
            $GLOBALS['ods_buffer']
        );

        $this->assertContains(
            '</office:body>',
            $GLOBALS['ods_buffer']
        );

        $this->assertContains(
            '</office:document-content>',
            $GLOBALS['ods_buffer']
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportOds::exportDBHeader
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
     * Test for PhpMyAdmin\Plugins\Export\ExportOds::exportDBFooter
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
     * Test for PhpMyAdmin\Plugins\Export\ExportOds::exportDBCreate
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
     * Test for PhpMyAdmin\Plugins\Export\ExportOds::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $flags = array();
        $a = new stdClass;
        $flags[] = $a;

        $a = new stdClass;
        $a->blob = true;
        $flags[] = $a;

        $a = new stdClass;
        $a->blob = false;
        $a->type = 'date';
        $flags[] = $a;

        $a = new stdClass;
        $a->blob = false;
        $a->type = 'time';
        $flags[] = $a;

        $a = new stdClass;
        $a->blob = false;
        $a->type = 'datetime';
        $flags[] = $a;

        $a = new stdClass;
        $a->numeric = true;
        $a->type = 'none';
        $a->blob = false;
        $flags[] = $a;

        $a = new stdClass;
        $a->numeric = true;
        $a->type = 'real';
        $a->blob = true;
        $flags[] = $a;

        $a = new stdClass;
        $a->type = "dummy";
        $a->blob = false;
        $a->numeric = false;
        $flags[] = $a;

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->exactly(8))
            ->method('fieldFlags')
            ->willReturnOnConsecutiveCalls(
                'BINARYTEST',
                'binary',
                '',
                '',
                '',
                '',
                '',
                ''
            );

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(8));

        $dbi->expects($this->at(11))
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    array(
                        null, '01-01-2000', '01-01-2000', '01-01-2000 10:00:00',
                        "01-01-2014 10:02:00", "t>s", "a&b", "<"
                    )
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['mediawiki_caption'] = true;
        $GLOBALS['mediawiki_headers'] = true;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = "&";

        $this->assertTrue(
            $this->object->exportData(
                'db', 'table', "\n", "example.com", "SELECT"
            )
        );

        $this->assertEquals(
            '<table:table table:name="table"><table:table-row><table:table-cell ' .
            'office:value-type="string"><text:p>&amp;</text:p></table:table-cell>' .
            '<table:table-cell office:value-type="string"><text:p></text:p>' .
            '</table:table-cell><table:table-cell office:value-type="date" office:' .
            'date-value="2000-01-01" table:style-name="DateCell"><text:p>01-01' .
            '-2000</text:p></table:table-cell><table:table-cell office:value-type=' .
            '"time" office:time-value="PT10H00M00S" table:style-name="TimeCell">' .
            '<text:p>01-01-2000 10:00:00</text:p></table:table-cell><table:table-' .
            'cell office:value-type="date" office:date-value="2014-01-01T10:02:00"' .
            ' table:style-name="DateTimeCell"><text:p>01-01-2014 10:02:00' .
            '</text:p></table:table-cell><table:table-cell office:value-type=' .
            '"float" office:value="t>s" ><text:p>t&gt;s</text:p>' .
            '</table:table-cell><table:table-cell office:value-type="float" ' .
            'office:value="a&b" ><text:p>a&amp;b</text:p></table:table-cell>' .
            '<table:table-cell office:value-type="string"><text:p>&lt;</text:p>' .
            '</table:table-cell></table:table-row></table:table>',
            $GLOBALS['ods_buffer']
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportOds::exportData
     *
     * @return void
     */
    public function testExportDataWithFieldNames()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $flags = array();

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->any())
            ->method('fieldFlags')
            ->will($this->returnValue('BINARYTEST'));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(2));

        $dbi->expects($this->at(5))
            ->method('fieldName')
            ->will($this->returnValue('fna\"me'));

        $dbi->expects($this->at(6))
            ->method('fieldName')
            ->will($this->returnValue('fnam/<e2'));

        $dbi->expects($this->at(7))
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    null
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['mediawiki_caption'] = true;
        $GLOBALS['mediawiki_headers'] = true;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = "&";
        $GLOBALS['foo_columns'] = true;

        $this->assertTrue(
            $this->object->exportData(
                'db', 'table', "\n", "example.com", "SELECT"
            )
        );

        $this->assertEquals(
            '<table:table table:name="table"><table:table-row><table:table-cell ' .
            'office:value-type="string"><text:p>fna&quot;me</text:p></table:table' .
            '-cell><table:table-cell office:value-type="string"><text:p>' .
            'fnam/&lt;e2</text:p></table:table-cell></table:table-row>' .
            '</table:table>',
            $GLOBALS['ods_buffer']
        );

        // with no row count
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $flags = array();

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(true)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT', DatabaseInterface::CONNECT_USER, DatabaseInterface::QUERY_UNBUFFERED)
            ->will($this->returnValue(true));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(true)
            ->will($this->returnValue(0));

        $dbi->expects($this->once())
            ->method('fetchRow')
            ->with(true)
            ->will(
                $this->returnValue(
                    null
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['mediawiki_caption'] = true;
        $GLOBALS['mediawiki_headers'] = true;
        $GLOBALS['what'] = 'foo';
        $GLOBALS['foo_null'] = "&";
        $GLOBALS['ods_buffer'] = '';

        $this->assertTrue(
            $this->object->exportData(
                'db', 'table', "\n", "example.com", "SELECT"
            )
        );

        $this->assertEquals(
            '<table:table table:name="table"><table:table-row></table:table-row>' .
            '</table:table>',
            $GLOBALS['ods_buffer']
        );
    }
}
