<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\plugins\export\ExportJson class
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\plugins\export\ExportJson;

require_once 'libraries/export.lib.php';
require_once 'libraries/config.default.php';
require_once 'export.php';
require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\plugins\export\ExportJson class
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class ExportJsonTest extends PMATestCase
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
     * Test for PMA\libraries\plugins\export\ExportJson::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod('PMA\libraries\plugins\export\ExportJson', 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty('PMA\libraries\plugins\export\ExportJson', 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            'PMA\libraries\properties\plugins\ExportPluginProperties',
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
            'PMA\libraries\properties\options\items\HiddenPropertyItem',
            $property
        );

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );

    }

    /**
     * Test for PMA\libraries\plugins\export\ExportJson::exportHeader
     *
     * @return void
     */
    public function testExportHeader()
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString(
            '/**' . "\n"
            . ' Export to JSON plugin for PHPMyAdmin' . "\n"
            . ' @version ' . PMA_VERSION . "\n"
            . ' */' . "\n" . "\n"
        );

        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportJson::exportFooter
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
     * Test for PMA\libraries\plugins\export\ExportJson::exportDBHeader
     *
     * @return void
     */
    public function testExportDBHeader()
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString(
            "// Database 'testDB'\n"
        );

        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
    }

    /**
     * Test for PMA\libraries\plugins\export\ExportJson::exportDBFooter
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
     * Test for PMA\libraries\plugins\export\ExportJson::exportDBCreate
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
     * Test for PMA\libraries\plugins\export\ExportJson::exportData
     *
     * @return void
     */
    public function testExportData()
    {
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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
            "\n// db.tbl\n\n" .
            "[{\"f1\":\"foo\"}, {\"f1\":\"bar\"}]\n"
        );

        $this->assertTrue(
            $this->object->exportData('db', 'tbl', "\n", "example.com", "SELECT")
        );
    }
}
