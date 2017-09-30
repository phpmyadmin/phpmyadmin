<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Plugins\Export\ExportJson class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Plugins\Export\ExportJson;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * tests for PhpMyAdmin\Plugins\Export\ExportJson class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportJsonTest extends PmaTestCase
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
        $this->object = new ExportJson();
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
     * Test for PhpMyAdmin\Plugins\Export\ExportJson::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PhpMyAdmin\Plugins\Export\ExportJson', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PhpMyAdmin\Plugins\Export\ExportJson', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PhpMyAdmin\Properties\Plugins\ExportPluginProperties',
            $properties
        );

        $this->assertEquals(
            'JSON',
            $properties->getText()
        );

        $this->assertEquals(
            'json',
            $properties->getExtension()
        );

        $this->assertEquals(
            'text/plain',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
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
            'PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem',
            $property
        );

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );

    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportJson::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString(
            "[\n"
            . '{"type":"header","version":"' . PMA_VERSION
            . '","comment":"Export to JSON plugin for PHPMyAdmin"},'
            . "\n"
        );

        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportJson::exportFooter
     *
     * @return void
     */
    public function testExportFooter()
    {
        $this->expectOutputString(
            ']'
        );

        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportJson::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString(
            '{"type":"database","name":"testDB"},' . "\n"
        );

        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportJson::exportDBFooter
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
     * Test for PhpMyAdmin\Plugins\Export\ExportJson::exportDBCreate
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
     * Test for PhpMyAdmin\Plugins\Export\ExportJson::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(null)
            ->will($this->returnValue(1));

        $dbi->expects($this->at(2))
            ->method('fieldName')
            ->with(null, 0)
            ->will($this->returnValue('f1'));

        $dbi->expects($this->at(3))
            ->method('fetchRow')
            ->with(null)
            ->will($this->returnValue(array('foo')));

        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->with(null)
            ->will($this->returnValue(array('bar')));

        $dbi->expects($this->at(5))
            ->method('fetchRow')
            ->with(null)
            ->will($this->returnValue(null));

        $GLOBALS['dbi'] = $dbi;

        $this->expectOutputString(
            '{"type":"table","name":"tbl","database":"db","data":'
            . "\n[\n"
            . '{"f1":"foo"},'
            . "\n"
            . '{"f1":"bar"}'
            . "\n]\n}\n"
        );

        $this->assertTrue(
            $this->object->exportData('db', 'tbl', "\n", "example.com", "SELECT")
        );
    }
}
