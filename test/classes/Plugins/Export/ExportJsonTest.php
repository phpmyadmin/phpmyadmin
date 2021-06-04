<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\ExportJson;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use function array_shift;

/**
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
        parent::defineVersionConstants();
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

    public function testExportHeader(): void
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

    public function testExportFooter(): void
    {
        $GLOBALS['crlf'] = '';

        $this->expectOutputString(
            ']'
        );

        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['crlf'] = "\n";

        $this->expectOutputString(
            '{"type":"database","name":"testDB"},' . "\n"
        );

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
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $a = new stdClass();
        $a->blob = false;
        $a->numeric = false;
        $a->type = 'string';
        $a->name = 'f1';
        $a->charsetnr = 33;
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
            ->with(null)
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

    public function testExportComplexData(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $normalString = new stdClass();
        $normalString->blob = false;
        $normalString->numeric = false;
        $normalString->type = 'string';
        $normalString->name = 'f1';
        $normalString->charsetnr = 33;
        $normalString->length = 20;
        $flags[] = $normalString;
        $binaryField = new stdClass();
        $binaryField->blob = false;
        $binaryField->numeric = false;
        $binaryField->type = 'string';
        $binaryField->name = 'f1';
        $binaryField->charsetnr = 63;
        $binaryField->length = 20;
        $flags[] = $binaryField;
        $textField = new stdClass();
        $textField->blob = false;
        $textField->numeric = false;
        $textField->type = 'blob';
        $textField->name = 'f1';
        $textField->charsetnr = 23;
        $textField->length = 20;
        $flags[] = $textField;
        $blobField = new stdClass();
        $blobField->blob = false;
        $blobField->numeric = false;
        $blobField->type = 'blob';
        $blobField->name = 'f1';
        $blobField->charsetnr = 63;
        $blobField->length = 20;
        $flags[] = $blobField;

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(null)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(null)
            ->will($this->returnValue(4));

        $dbi->expects($this->exactly(4))
            ->method('fieldName')
            ->withConsecutive(
                [null, 0],
                [null, 1],
                [null, 2],
                [null, 3]
            )
            ->willReturnOnConsecutiveCalls(
                'f1',
                'f2',
                'f3',
                'f4'
            );

        $dbi->expects($this->exactly(4))
            ->method('fetchRow')
            ->withConsecutive(
                [null],
                [null],
                [null],
                [null]
            )
            ->willReturnOnConsecutiveCalls(
                // normalString binaryField textField blobField
                ['"\'"><iframe onload=alert(1)>шеллы', '0x12346857fefe', "My awesome\nText", '0xaf1234f68c57fefe'],
                [null, null, null, null],
                ['', '0x1', 'шеллы', '0x2'],
                null// No more data
            );

        $GLOBALS['dbi'] = $dbi;

        $this->expectOutputString(
            '{"type":"table","name":"tbl","database":"db","data":'
            . "\n[\n"
            . '{"f1":"\"\'\"><iframe onload=alert(1)>\u0448\u0435\u043b\u043b\u044b",'
                . '"f2":"0x3078313233343638353766656665",'
                . '"f3":"My awesome\nText","f4":"0x307861663132333466363863353766656665"},' . "\n"
            . '{"f1":null,"f2":null,"f3":null,"f4":null},' . "\n"
            . '{"f1":"","f2":"0x307831","f3":"\u0448\u0435\u043b\u043b\u044b","f4":"0x307832"}' . "\n"
            . "]\n}\n"
        );

        $this->assertTrue(
            $this->object->exportData('db', 'tbl', "\n", 'example.com', 'SELECT')
        );
    }

    public function testExportRawComplexData(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flags = [];
        $normalString = new stdClass();
        $normalString->blob = false;
        $normalString->numeric = false;
        $normalString->type = 'string';
        $normalString->name = 'f1';
        $normalString->charsetnr = 33;
        $normalString->length = 20;
        $flags[] = $normalString;
        $binaryField = new stdClass();
        $binaryField->blob = false;
        $binaryField->numeric = false;
        $binaryField->type = 'string';
        $binaryField->name = 'f1';
        $binaryField->charsetnr = 63;
        $binaryField->length = 20;
        $flags[] = $binaryField;
        $textField = new stdClass();
        $textField->blob = false;
        $textField->numeric = false;
        $textField->type = 'blob';
        $textField->name = 'f1';
        $textField->charsetnr = 23;
        $textField->length = 20;
        $flags[] = $textField;
        $blobField = new stdClass();
        $blobField->blob = false;
        $blobField->numeric = false;
        $blobField->type = 'blob';
        $blobField->name = 'f1';
        $blobField->charsetnr = 63;
        $blobField->length = 20;
        $flags[] = $blobField;

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with(null)
            ->will($this->returnValue($flags));

        $dbi->expects($this->once())
            ->method('numFields')
            ->with(null)
            ->will($this->returnValue(4));

        $dbi->expects($this->exactly(4))
            ->method('fieldName')
            ->withConsecutive(
                [null, 0],
                [null, 1],
                [null, 2],
                [null, 3]
            )
            ->willReturnOnConsecutiveCalls(
                'f1',
                'f2',
                'f3',
                'f4'
            );

        $dbi->expects($this->exactly(4))
            ->method('fetchRow')
            ->withConsecutive(
                [null],
                [null],
                [null],
                [null]
            )
            ->willReturnOnConsecutiveCalls(
                // normalString binaryField textField blobField
                ['"\'"><iframe onload=alert(1)>шеллы', '0x12346857fefe', "My awesome\nText", '0xaf1234f68c57fefe'],
                [null, null, null, null],
                ['', '0x1', 'шеллы', '0x2'],
                null// No more data
            );

        $GLOBALS['dbi'] = $dbi;

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
            $this->object->exportRawQuery('example.com', 'SELECT', "\n")
        );
    }
}
