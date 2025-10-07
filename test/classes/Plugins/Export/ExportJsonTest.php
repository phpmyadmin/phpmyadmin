<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Plugins\Export\ExportJson;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Version;
use ReflectionMethod;
use ReflectionProperty;

use function array_shift;

use const PHP_VERSION_ID;

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

    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportJson::class, 'setProperties');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportJson::class, 'properties');
        if (PHP_VERSION_ID < 80100) {
            $attrProperties->setAccessible(true);
        }

        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame('JSON', $properties->getText());

        self::assertSame('json', $properties->getExtension());

        self::assertSame('text/plain', $properties->getMimeType());

        self::assertSame('Options', $properties->getOptionsText());

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame('Format Specific Options', $options->getName());

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('general_opts', $generalOptions->getName());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertSame('structure_or_data', $property->getName());
    }

    public function testExportHeader(): void
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString(
            "[\n"
            . '{"type":"header","version":"' . Version::VERSION
            . '","comment":"Export to JSON plugin for phpMyAdmin"},'
            . "\n"
        );

        self::assertTrue($this->object->exportHeader());
    }

    public function testExportFooter(): void
    {
        $GLOBALS['crlf'] = '';

        $this->expectOutputString(']');

        self::assertTrue($this->object->exportFooter());
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString('{"type":"database","name":"testDB"},' . "\n");

        self::assertTrue($this->object->exportDBHeader('testDB'));
    }

    public function testExportDBFooter(): void
    {
        self::assertTrue($this->object->exportDBFooter('testDB'));
    }

    public function testExportDBCreate(): void
    {
        self::assertTrue($this->object->exportDBCreate('testDB', 'database'));
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

        self::assertTrue($this->object->exportData(
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

        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table_complex',
            "\n",
            'example.com',
            'SELECT * FROM `test_db`.`test_table_complex`;'
        ));
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

        self::assertTrue($this->object->exportRawQuery(
            'example.com',
            null,
            'SELECT * FROM `test_db`.`test_table_complex`;',
            "\n"
        ));
    }
}
