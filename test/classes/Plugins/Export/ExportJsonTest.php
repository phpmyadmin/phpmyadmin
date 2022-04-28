<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Export;
use PhpMyAdmin\Plugins\Export\ExportJson;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Version;
use ReflectionMethod;
use ReflectionProperty;

use function array_shift;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportJson
 * @group medium
 */
class ExportJsonTest extends AbstractTestCase
{
    /** @var ExportJson */
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
        $this->object = new ExportJson(
            new Relation($GLOBALS['dbi']),
            new Export($GLOBALS['dbi']),
            new Transformations()
        );
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportJson::class, 'setProperties');
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportJson::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(ExportPluginProperties::class, $properties);

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

        $this->assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(HiddenPropertyItem::class, $property);

        $this->assertEquals(
            'structure_or_data',
            $property->getName()
        );
    }

    public function testExportHeader(): void
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString(
            "[\n"
            . '{"type":"header","version":"' . Version::VERSION
            . '","comment":"Export to JSON plugin for PHPMyAdmin"},'
            . "\n"
        );

        $this->assertTrue(
            $this->object->exportHeader()
        );
    }

    public function testExportFooter(): void
    {
        $GLOBALS['crlf'] = '';

        $this->expectOutputString(']');

        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString('{"type":"database","name":"testDB"},' . "\n");

        $this->assertTrue(
            $this->object->exportDBHeader('testDB')
        );
    }

    public function testExportDBFooter(): void
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB')
        );
    }

    public function testExportDBCreate(): void
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database')
        );
    }

    public function testExportData(): void
    {
        $this->expectOutputString(
            '{"type":"table","name":"test_table","database":"test_db","data":' . "\n"
            . '[' . "\n"
            . '{"id":"1","name":"abcd","datetimefield":"2011-01-20 02:00:02"},' . "\n"
            . '{"id":"2","name":"foo","datetimefield":"2010-01-20 02:00:02"},' . "\n"
            . '{"id":"3","name":"Abcd","datetimefield":"2012-01-20 02:00:02"}' . "\n"
            . ']' . "\n"
            . '}' . "\n"
        );

        $this->assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'localhost',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
    }

    public function testExportComplexData(): void
    {
        // normalString binaryField textField blobField
        $this->expectOutputString(
            '{"type":"table","name":"test_table_complex","database":"test_db","data":'
            . "\n[\n"
            . '{"f1":"\"\'\"><iframe onload=alert(1)>\u0448\u0435\u043b\u043b\u044b",'
                . '"f2":"0x3078313233343638353766656665",'
                . '"f3":"My awesome\nText","f4":"0x307861663132333466363863353766656665"},' . "\n"
            . '{"f1":null,"f2":null,"f3":null,"f4":null},' . "\n"
            . '{"f1":"","f2":"0x307831","f3":"\u0448\u0435\u043b\u043b\u044b","f4":"0x307832"}' . "\n"
            . "]\n}\n"
        );

        $this->assertTrue(
            $this->object->exportData(
                'test_db',
                'test_table_complex',
                "\n",
                'example.com',
                'SELECT * FROM `test_db`.`test_table_complex`;'
            )
        );
    }

    public function testExportRawComplexData(): void
    {
        $this->expectOutputString(
            '{"type":"raw","data":'
            . "\n[\n"
            . '{"f1":"\"\'\"><iframe onload=alert(1)>\u0448\u0435\u043b\u043b\u044b",'
                . '"f2":"0x3078313233343638353766656665",'
                . '"f3":"My awesome\nText","f4":"0x307861663132333466363863353766656665"},' . "\n"
            . '{"f1":null,"f2":null,"f3":null,"f4":null},' . "\n"
            . '{"f1":"","f2":"0x307831","f3":"\u0448\u0435\u043b\u043b\u044b","f4":"0x307832"}' . "\n"
            . "]\n}\n"
        );

        $this->assertTrue(
            $this->object->exportRawQuery(
                'example.com',
                'SELECT * FROM `test_db`.`test_table_complex`;',
                "\n"
            )
        );
    }
}
