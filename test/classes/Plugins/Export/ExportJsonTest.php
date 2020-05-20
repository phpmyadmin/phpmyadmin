<?php
/**
 * tests for PhpMyAdmin\Plugins\Export\ExportJson class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportJson;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use function array_shift;

/**
 * tests for PhpMyAdmin\Plugins\Export\ExportJson class
 *
 * @group medium
 */
class ExportJsonTest extends PmaTestCase
{
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
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
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\ExportJson::setProperties
     *
     * @return void
     */
    public function testSetProperties()
    {
        $method = new ReflectionMethod(ExportJson::class, 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportJson::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            ExportPluginProperties::class,
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
            OptionsPropertyRootGroup::class,
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            HiddenPropertyItem::class,
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
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $a = new stdClass();
        $a->blob = false;
        $a->numeric = false;
        $a->type = 'string';
        $a->name = 'f1';
        $a->length = 20;
        $flags[] = $a;

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(null)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(null)
            ->will($this->returnValue(1));

        $dbi->expects($this->at(3))
            ->method('fieldName')
            ->with(null, 0)
            ->will($this->returnValue('f1'));

        $dbi->expects($this->at(4))
            ->method('fetchRow')
            ->with(null)
            ->will($this->returnValue(['foo']));

        $dbi->expects($this->at(5))
            ->method('fetchRow')
            ->with(null)
            ->will($this->returnValue(['bar']));

        $dbi->expects($this->at(6))
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
            $this->object->exportData('db', 'tbl', "\n", 'example.com', 'SELECT')
        );
    }
}
